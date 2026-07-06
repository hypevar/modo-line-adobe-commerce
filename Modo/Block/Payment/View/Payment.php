<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace Line\Modo\Block\Payment\View;

use Magento\Checkout\Model\Session;
use Magento\Framework\CurrencyInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Line\Modo\Api\ConfigInterface;

class Payment extends Template
{
    protected $_template = 'Line_Modo::order/view/payment.phtml';

    /**
     * @param Context $context
     * @param CurrencyInterface $currency
     * @param Session $checkoutSession
     * @param ArrayManager $arrayManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        readonly private ConfigInterface $config,
        readonly private CurrencyInterface $currency,
        readonly private Session $checkoutSession,
        readonly private ArrayManager $arrayManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }


    /**
     * @param string|null $path
     * @param string|null $defaultValue
     * @return string|null|array
     */
    private function getPaymentAdditionalInformation(string $path = null, string $defaultValue = null)
    {
        $data = $this->getPayment()->getAdditionalInformation() ?? [];
        return $path !== null ? $this->arrayManager->get($path, $data, $defaultValue) : $data;
    }

    /**
     * @return false|float|\Magento\Framework\DataObject|OrderPaymentInterface|mixed|null
     */
    public function getPayment()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        return $order->getPayment();
    }

    /**
     * @return array|string
     */
    public function getLinePaymentInfo()
    {
        return $this->getPaymentAdditionalInformation();
    }


    /**
     * Retrieves the expiration date in 'd/m' format.
     *
     * @return string The formatted expiration date or an empty string if it cannot be determined.
     */
    public function getExpirationDate()
    {
        $dateString = $this->getPaymentAdditionalInformation('expires_At', '');
        if (!empty($dateString)) {
            try {
                if (is_numeric($dateString)) {
                    $date = new \DateTime();
                    $date->setTimestamp((int)$dateString);
                } else {
                    $date = new \DateTime($dateString);
                }
                return $date->format('d/m');
            } catch (\Exception $e) {
                return '';
            }
        }
        return '';
    }

    /**
     * Return true if Modo pay is the payment method
     *
     * @return bool
     */
    public function isLineModoPaymentMethod()
    {
        $payment = $this->getPayment();
        if (!$payment) {
            return false;
        }
        return $payment->getMethod() === ConfigInterface::PAYMENT_CODE;
    }

    /**
     * @param $amount
     * @param $currency
     * @return string
     * @throws \Magento\Framework\Currency\Exception\CurrencyException
     */
    public function toCurrency($amount, $currency = null)
    {
        return $this->currency->toCurrency($amount, [
            'currency' => $currency,
        ]);
    }

    /**
     * @return string
     */
    public function getFrameUrl()
    {
        return $this->config->getFrameUrl();
    }

    /**
     * @return mixed
     */
    public function getPublikKey()
    {
        return $this->config->getClientPubKey();
    }

    /**
     * Returns the URL to retry the payment for the current session order.
     *
     * @return string
     */
    public function getRetryUrl(): string
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order->getId()) {
            return '';
        }

        return $this->getUrl('line/modo/retry', [
            'order_id' => $order->getId(),
            'key' => $this->config->getOrderKey($order),
        ]);
    }

    /**
     * Returns the URL to poll the current payment status.
     *
     * @return string
     */
    public function getStatusUrl(): string
    {
        $paymentInfo = $this->getLinePaymentInfo();
        $paymentId = $paymentInfo['id'] ?? '';

        if (!$paymentId) {
            return '';
        }

        return $this->getUrl('line/modo/status', ['payment_id' => $paymentId]);
    }

    /**
     * Returns non-empty theme attributes from admin config.
     * Keys are data-attribute names (e.g. 'data-primary-color').
     *
     * @return array<string, string>
     */
    public function getThemeData(): array
    {
        return $this->config->getTheme();
    }

    /**
     * @return string
     */
    public function getMobileCallbackUrl(): string
    {
        return $this->getUrl(
            'checkout/onepage/success',
            ['_secure' => true]);
    }
}
