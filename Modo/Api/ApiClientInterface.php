<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace Line\Modo\Api;

use Exception;

interface ApiClientInterface
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_UNDERPAID = 'underpaid';
    public const STATUS_OVERPAID = 'overpaid';

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return mixed
     * @throws Exception
     */
    public function getPayData(\Magento\Sales\Api\Data\OrderInterface $order);

    /**
     * @param $paymentId
     * @return array
     */
    public function getPayment($paymentId): array;

}
