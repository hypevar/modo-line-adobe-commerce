<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Modo\Controller\Modo;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Line\Modo\Api\ApiClientInterface;
use Line\Modo\Api\ConfigInterface;
use Line\Modo\Api\PaymentProcessorInterface;

class Callback implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @param Request $request
     * @param JsonFactory $resultJsonFactory
     * @param ConfigInterface $config
     * @param ApiClientInterface $apiClient
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param PaymentProcessorInterface $paymentProcessor
     */
    public function __construct(
        private readonly Request $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ConfigInterface $config,
        private readonly ApiClientInterface $apiClient,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger,
        private readonly PaymentProcessorInterface $paymentProcessor,
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
     * Execute
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $bodyParams = $this->request->getBodyParams();
        $resultJson = $this->resultJsonFactory->create();

        try {
            if ($this->config->isDebugMode()) {
                $this->logger->info('Callback return to payment', ['bodyParams' => $bodyParams]);
            }
            if (!isset($bodyParams['paymentId'])) {
                throw new LocalizedException(__('Invalid payment data.'));
            }
            $paymentId = $bodyParams['paymentId'];
            $modoPayment = $this->apiClient->getPayment($paymentId);
            if (empty($modoPayment) || !isset($modoPayment['magento'])) {
                throw new LocalizedException(__('There was an error processing your payment.'));
            }
            $order = $this->orderRepository->get($modoPayment['magento']['order_id']);

            $storedPaymentId = $order->getPayment()->getAdditionalInformation('id') ?? '';
            if ($storedPaymentId !== $paymentId) {
                $this->logger->warning('Callback paymentId mismatch', [
                    'received' => $paymentId,
                    'stored' => $storedPaymentId,
                    'order_id' => $order->getId(),
                ]);
                throw new LocalizedException(__('Invalid payment data.'));
            }

            $response = $this->paymentProcessor->execute($order, $paymentId, $modoPayment);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $resultJson->setData([
                'success' => false,
                'message' => 'An error occurred while processing the payment.',
            ]);
        }

        return $resultJson->setData(['success' => true]);
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
