<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Modo\Controller\Modo;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Line\Modo\Api\ApiClientInterface;

class Status implements HttpGetActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param Session $checkoutSession
     * @param ApiClientInterface $apiClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly Session $checkoutSession,
        private readonly ApiClientInterface $apiClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Returns the current payment status for the active session order.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();
        $paymentId = (string) $this->request->getParam('payment_id', '');

        if (!$paymentId) {
            return $resultJson->setData([
                'status' => 'ERROR',
                'message' => 'Missing payment_id',
            ]);
        }

        try {
            $order = $this->checkoutSession->getLastRealOrder();

            if (!$order->getId()) {
                return $resultJson->setData([
                    'status' => 'ERROR',
                    'message' => 'No active session order',
                ]);
            }

            $additionalInfo = $order->getPayment()->getAdditionalInformation();
            $sessionPaymentId = $additionalInfo['id'] ?? '';

            if ($sessionPaymentId !== $paymentId) {
                return $resultJson->setData([
                    'status' => 'ERROR',
                    'message' => 'Invalid payment session',
                ]);
            }

            $modoPayment = $this->apiClient->getPayment($paymentId);

            if (empty($modoPayment)) {
                return $resultJson->setData(['status' => 'PENDING']);
            }

            return $resultJson->setData([
                'status' => $modoPayment['status'] ?? 'PENDING',
            ]);
        } catch (Exception $e) {
            $this->logger->error(
                'Line Modo status check failed: ' . $e->getMessage(),
                ['payment_id' => $paymentId],
            );

            return $resultJson->setData([
                'status' => 'ERROR',
                'message' => 'Status check failed',
            ]);
        }
    }
}
