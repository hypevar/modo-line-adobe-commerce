<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Modo\Controller\Modo;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;
use Line\Modo\Api\ConfigInterface;
use Line\Modo\Api\WebhookInterface;

class Webhook implements HttpPostActionInterface, CsrfAwareActionInterface, WebhookInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param Json $json
     * @param ConfigInterface $config
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param TransactionFactory $transactionFactory
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly Json $json,
        private readonly ConfigInterface $config,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceSender $invoiceSender,
        private readonly TransactionFactory $transactionFactory,
        private readonly StatusCollectionFactory $statusCollectionFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Process an incoming webhook event from Line/Modo.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();
        $rawBody = $this->request->getContent();

        try {
            $this->verifySignature($rawBody);
        } catch (LocalizedException $e) {
            $this->logger->warning('Webhook signature rejected: ' . $e->getMessage());
            return $resultJson->setHttpResponseCode(400)->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $payload = $this->json->unserialize($rawBody);

            $eventId = $payload['eventId'] ?? null;
            $eventType = $payload['eventType'] ?? null;
            $paymentId = $payload['payment']['id'] ?? null;
            $reference = $payload['payment']['reference'] ?? null;
            $status = $payload['payment']['status'] ?? null;
            $transactionId = $payload['provider']['transactionId'] ?? '';
            $result = $payload['result'] ?? [];

            if ($this->config->isDebugMode()) {
                $this->logger->info('Webhook received', ['payload' => $payload]);
            }

            if (!$status || !$paymentId || !$reference) {
                $this->logger->warning('Webhook incomplete payload', ['payload' => $payload]);
                return $resultJson->setData(['success' => true]);
            }

            $incrementId = $this->resolveIncrementId($reference);
            $order = $this->loadOrderByIncrementId($incrementId);

            if ($eventId && $this->isDuplicate($order, $eventId)) {
                $this->logger->info('Webhook duplicate skipped', ['eventId' => $eventId]);
                return $resultJson->setData(['success' => true]);
            }

            $this->routeEvent($status, $order, $paymentId, $transactionId, $result);

            if ($eventId) {
                $this->markProcessed($order, $eventId);
            }
        } catch (\Exception $e) {
            $this->logger->error('Webhook error: ' . $e->getMessage(), ['exception' => $e]);
        }

        return $resultJson->setData(['success' => true]);
    }

    /**
     * Verify the HMAC-SHA256 signature from the X-Line-Signature header.
     *
     * Expected header format: t=<timestamp>,v1=<hmac>
     *
     * @param string $rawBody
     * @return void
     * @throws LocalizedException
     */
    private function verifySignature(string $rawBody): void
    {
        $header = $this->request->getHeader('X-Line-Signature');
        if (!$header) {
            throw new LocalizedException(__('Missing signature header.'));
        }

        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = explode('=', $part, 2) + [null, null];
            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['t'] ?? null;
        $receivedHmac = $parts['v1'] ?? null;

        if (!$timestamp || !$receivedHmac) {
            throw new LocalizedException(__('Invalid signature format.'));
        }

        if (abs(time() - (int) $timestamp) > self::SIGNATURE_TOLERANCE_SECONDS) {
            throw new LocalizedException(__('Webhook timestamp out of tolerance.'));
        }

        $secret = $this->config->getClientSecret();
        $expectedHmac = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);

        if (!hash_equals($expectedHmac, $receivedHmac)) {
            throw new LocalizedException(__('Invalid webhook signature.'));
        }
    }

    /**
     * Extract the order increment ID from a payment reference string (format: {increment_id}||{timestamp}).
     *
     * @param string $reference
     * @return string
     * @throws LocalizedException
     */
    private function resolveIncrementId(string $reference): string
    {
        $parts = explode(self::REFERENCE_SEPARATOR, $reference, 2);
        if (count($parts) === 2 && $parts[0] !== '') {
            return $parts[0];
        }
        throw new LocalizedException(__('Cannot resolve increment ID from reference: %1', $reference));
    }

    /**
     * Load an order by its increment ID.
     *
     * @param string $incrementId
     * @return OrderInterface
     * @throws LocalizedException
     */
    private function loadOrderByIncrementId(string $incrementId): OrderInterface
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId)
            ->setPageSize(1)
            ->create();

        $results = $this->orderRepository->getList($criteria)->getItems();

        if (empty($results)) {
            throw new LocalizedException(__('Order not found for increment ID: %1', $incrementId));
        }

        return reset($results);
    }

    /**
     * Check whether this event ID was already processed for the given order.
     *
     * @param OrderInterface $order
     * @param string $eventId
     * @return bool
     */
    private function isDuplicate(OrderInterface $order, string $eventId): bool
    {
        $processed = $order->getPayment()->getAdditionalInformation(self::IDEMPOTENCY_KEY) ?? [];
        return in_array($eventId, (array) $processed, true);
    }

    /**
     * Record an event ID as processed in the order's payment additional information.
     *
     * @param OrderInterface $order
     * @param string $eventId
     * @return void
     */
    private function markProcessed(OrderInterface $order, string $eventId): void
    {
        $payment = $order->getPayment();
        $processed = $payment->getAdditionalInformation(self::IDEMPOTENCY_KEY) ?? [];
        $processed[] = $eventId;
        $payment->setAdditionalInformation(self::IDEMPOTENCY_KEY, $processed);
        $this->orderRepository->save($order);
    }

    /**
     * Dispatch the event to the appropriate handler based on payment status.
     *
     * @param string $status
     * @param OrderInterface $order
     * @param string $paymentId
     * @param string $transactionId
     * @param array $result
     * @return void
     */
    private function routeEvent(
        string $status,
        OrderInterface $order,
        string $paymentId,
        string $transactionId,
        array $result
    ): void {
        match ($status) {
            self::PAYMENT_STATUS_SUCCESS => $this->processCompletedPayment($order, $paymentId, $transactionId),
            self::PAYMENT_STATUS_FAILED => $this->processFailedPayment($order, $paymentId, $result),
            default => $this->logger->info('Webhook unhandled status', [
                'status' => $status,
                'orderId' => $order->getId(),
                'paymentId' => $paymentId,
            ]),
        };
    }

    /**
     * Apply the configured paid status, optionally generate an invoice and add a status history comment.
     *
     * @param OrderInterface $order
     * @param string $paymentId
     * @param string $transactionId
     * @return void
     */
    private function processCompletedPayment(
        OrderInterface $order,
        string $paymentId,
        string $transactionId
    ): void {
        $statusPay = $this->config->getStatusPay();
        $state = $this->resolveStateForStatus($statusPay);
        if ($state !== null) {
            $order->setState($state);
        } else {
            $this->logger->warning('Webhook: could not resolve state for status', ['status' => $statusPay]);
        }
        $order->setStatus($statusPay);

        if ($this->config->canGenerateInvoice() && $order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setTransactionId($transactionId);
            $invoice->register();
            $invoice->setState(Invoice::STATE_PAID);
            $order->getPayment()->pay($invoice);

            $transaction = $this->transactionFactory->create();
            $transaction->addObject($invoice);
            $transaction->addObject($order);
            $transaction->save();

            $this->invoiceSender->send($invoice);
        }

        $order->addCommentToStatusHistory(__(
            'Payment approved via webhook. Status updated to: %1. Payment ID: %2. Transaction ID: %3.',
            $statusPay,
            $paymentId,
            $transactionId ?: 'N/A'
        ));

        $this->orderRepository->save($order);
    }

    /**
     * Log a failed payment, apply the rejected status and add an order comment with the error reason.
     *
     * @param OrderInterface $order
     * @param string $paymentId
     * @param array $result
     * @return void
     */
    private function processFailedPayment(OrderInterface $order, string $paymentId, array $result): void
    {
        $this->logger->info('Webhook payment not completed', [
            'orderId' => $order->getId(),
            'paymentId' => $paymentId,
            'result' => $result,
        ]);

        $statusRejected = $this->config->getStatusRejected();
        $state = $this->resolveStateForStatus($statusRejected);
        if ($state !== null) {
            $order->setState($state);
        } else {
            $this->logger->warning('Webhook: could not resolve state for status', ['status' => $statusRejected]);
        }
        $order->setStatus($statusRejected);

        $order->addCommentToStatusHistory(__(
            'Payment rejected via webhook. Status updated to: %1. Payment ID: %2.',
            $statusRejected,
            $paymentId
        ));

        if (isset($result['success']) && $result['success'] === false) {
            $this->addErrorComment($order, (string) ($result['errorCode'] ?? ''));
        }

        $this->orderRepository->save($order);
    }

    /**
     * Add a status history comment to the order describing the payment error.
     *
     * @param OrderInterface $order
     * @param string $errorCode
     * @return void
     */
    private function addErrorComment(OrderInterface $order, string $errorCode): void
    {
        $description = self::ERROR_CODE_DESCRIPTIONS[$errorCode] ?? $errorCode;

        $order->addCommentToStatusHistory(__('Payment rejected: %1 (%2)', __($description), $errorCode), false);
        $this->orderRepository->save($order);
    }

    /**
     * Resolve the Magento order state corresponding to a given status code.
     *
     * A status may be mapped to multiple states in sales_order_status_state.
     * This method returns the first matching state, or null if no mapping exists.
     *
     * @param string $statusCode
     * @return string|null
     */
    private function resolveStateForStatus(string $statusCode): ?string
    {
        $collection = $this->statusCollectionFactory->create();
        $collection->joinStates();
        $collection->addFieldToFilter('main_table.status', $statusCode);

        foreach ($collection as $item) {
            $state = $item->getData('state');
            if ($state !== null && $state !== '') {
                return $state;
            }
        }

        return null;
    }
}
