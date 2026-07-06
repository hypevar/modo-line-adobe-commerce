<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace Line\Modo\Controller\Modo;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;
use Line\Modo\Api\ApiClientInterface;
use Line\Modo\Api\ConfigInterface;

class Start implements HttpGetActionInterface
{
    /**
     * @param LoggerInterface $logger
     * @param RedirectFactory $redirectFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Session $checkoutSession
     * @param ConfigInterface $config
     * @param ApiClientInterface $apiClient
     * @param UrlInterface $urlBuilder
     * @param ManagerInterface $messageManager
     * @param OrderSender $orderSender
     * @param InstructionSender $instructionSender
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RedirectFactory $redirectFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Session $checkoutSession,
        private readonly ConfigInterface $config,
        private readonly ApiClientInterface $apiClient,
        private readonly UrlInterface $urlBuilder,
        private readonly ManagerInterface $messageManager,
        private readonly OrderSender $orderSender
    ) {
    }

    /**
     * Execute
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $orderId = $this->checkoutSession->getLastOrderId();
        $resultRedirect = $this->redirectFactory->create();
        $resultRedirect->setUrl($this->urlBuilder->getUrl('sales/order/view', ['order_id' => $orderId]));

        $order = null;
        try {
            if (!$orderId) {
                throw new LocalizedException(__('No order id specified.'));
            }

            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();

            $data = $this->apiClient->getPayData($order);

            $payment->setAdditionalInformation($data);

            $state = $this->config->getOrderStatus();


            $redirectUrl = $this->urlBuilder->getUrl('checkout/onepage/success');
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastOrderId($order->getId());


            $this->orderRepository->save($order);

            $resultRedirect->setUrl($redirectUrl);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An internal error occurred. Please try again later or contact with support.'));
        } finally {
            if ($order !== null) {
                //$this->orderSender->send($order);
            }
        }

        return $resultRedirect;
    }

}
