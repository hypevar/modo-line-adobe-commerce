<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Modo\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

/**
 * Order Statuses source model
 */
class PendingPayment extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_PENDING_PAYMENT
    ];
}
