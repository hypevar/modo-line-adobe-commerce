<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace Line\Modo\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface PaymentProcessorInterface
{
    /**
     * Execute the payment processor
     *
     * @param OrderInterface $order
     * @param string $paymentId
     * @param array $modoPayment
     * @param \Line\Modo\Api\Data\PaymentProcessorResponseInterface|null $previousResult
     * @return \Line\Modo\Api\Data\PaymentProcessorResponseInterface|null
     */
    public function execute(
        \Magento\Sales\Api\Data\OrderInterface $order,
        string $paymentId,
        array $modoPayment,
        ?\Line\Modo\Api\Data\PaymentProcessorResponseInterface $previousResult = null
    ): ?\Line\Modo\Api\Data\PaymentProcessorResponseInterface;
}
