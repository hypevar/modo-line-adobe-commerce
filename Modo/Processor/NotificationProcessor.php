<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace Line\Modo\Processor;

use Magento\Sales\Api\Data\OrderInterface;
use Line\Modo\Api\Data\PaymentProcessorResponseInterface;
use Line\Modo\Api\NotificationSenderInterface;
use Line\Modo\Api\PaymentProcessorInterface;

readonly class NotificationProcessor implements PaymentProcessorInterface
{
    /**
     * @param NotificationSenderInterface $notificationSender
     */
    public function __construct(
        private NotificationSenderInterface $notificationSender,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(
        OrderInterface $order,
        string $paymentId,
        array $modoPayment,
        ?PaymentProcessorResponseInterface $previousResult = null
    ): ?PaymentProcessorResponseInterface {
        $this->notificationSender->notifyInvoices($order, $previousResult->getInvoicesToNotify());

        return $previousResult;
    }
}
