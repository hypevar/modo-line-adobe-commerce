<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Modo\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Payment\Gateway\Config\Config as MagentoConfig;
use Magento\Sales\Api\Data\OrderInterface;
use Line\Modo\Api\ConfigInterface;
use Line\Modo\Api\NotificationSenderInterface;
use Line\Modo\Model\Config\Source\Environment;

class Config extends MagentoConfig implements ConfigInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
    ) {
        parent::__construct(
            $scopeConfig,
            ConfigInterface::PAYMENT_CODE
        );
    }


    /**
     * @inheritDoc
     */
    public function getEnvironment(): string
    {
        return (string)$this->getValue(self::XPATH_ENVIRONMENT);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(?string $environment = null)
    {
        $environment = $environment ?? $this->getEnvironment();
        return (string)($environment === Environment::ENV_PRODUCTION ?
            $this->getValue(self::XPATH_PRODUCTION_SECRET_KEY) :
            $this->getValue(self::XPATH_SANDBOX_SECRET_KEY));
    }

    /**
     * @inheritDoc
     */
    public function getClientPubKey(?string $environment = null)
    {
        $environment = $environment ?? $this->getEnvironment();
        return (string)($environment === Environment::ENV_PRODUCTION ?
            $this->getValue(self::XPATH_PRODUCTION_PUB_KEY) :
            $this->getValue(self::XPATH_SANDBOX_PUB_KEY));
    }

    /**
     * @inheritDoc
     */
    public function getNotificationEmailStatus(): string
    {
        return $this->getValue(self::XPATH_NOTIFICATION_EMAIL_STATUS)
            ?? NotificationSenderInterface::STATUS_DISABLED;
    }

    /**
     * @inheritDoc
     */
    public function getNotificationEmailTemplate(): string
    {
        return $this->getValue(self::XPATH_NOTIFICATION_EMAIL_TEMPLATE);
    }

    /**
     * @inheritDoc
     */
    public function getOrderKey(OrderInterface $order): string
    {
        return $this->encryptor->hash($order->getProtectCode());
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatus(): string
    {
        return (string)$this->getValue(self::XPATH_ORDER_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function getStatusPay()
    {
        return $this->getValue(self::XPATH_STATUS_PAY);
    }

    /**
     * @inheritDoc
     */
    public function getStatusRejected()
    {
        return $this->getValue(self::XPATH_STATUS_REJECTED);
    }

    /**
     * @inheritDoc
     */
    public function canGenerateInvoice(): bool
    {
        return (bool) $this->getValue(self::XPATH_GENERATE_INVOICE);
    }

    /**
     * @inheritDoc
     */
    public function getUrl(?string $environment = null): string
    {
        $environment = $environment ?? $this->getEnvironment();
        return (string)($environment === Environment::ENV_PRODUCTION ?
            $this->getValue(self::XPATH_PRODUCTION_ENDPOINT) :
            $this->getValue(self::XPATH_SANDBOX_ENDPOINT)
        );
    }

    /**
     * @inheritDoc
     */
    public function getFrameUrl(?string $environment = null): string
    {
        $environment = $environment ?? $this->getEnvironment();
        return (string)($environment === Environment::ENV_PRODUCTION ?
            $this->getValue(self::XPATH_FRAME_PRODUCTION_ENDPOINT) :
            $this->getValue(self::XPATH_FRAME_SANDBOX_ENDPOINT)
        );
    }

    /**
     * @inheritDoc
     */
    public function isDebugMode(): bool
    {
        return !!$this->getValue(self::XPATH_DEBUG);
    }

    /**
     * @inheritDoc
     */
    public function getCcCode(): string
    {
        return $this->getValue(self::XPATH_CC_CODE);
    }

    /**
     * @inheritDoc
     */
    public function getTheme(): array
    {
        $map = [
            'data-primary-color' => self::XPATH_THEME_PRIMARY_COLOR,
            'data-background-color' => self::XPATH_THEME_BACKGROUND_COLOR,
            'data-text-color' => self::XPATH_THEME_TEXT_COLOR,
            'data-border-color' => self::XPATH_THEME_BORDER_COLOR,
            'data-font-family' => self::XPATH_THEME_FONT_FAMILY,
            'data-font-size' => self::XPATH_THEME_FONT_SIZE,
            'data-border-radius' => self::XPATH_THEME_BORDER_RADIUS,
            'data-button-border-radius' => self::XPATH_THEME_BUTTON_BORDER_RADIUS,
            'data-button-text-color' => self::XPATH_THEME_BUTTON_TEXT_COLOR,
            'data-button-hover-color' => self::XPATH_THEME_BUTTON_HOVER_COLOR,
        ];

        $theme = [];
        foreach ($map as $attribute => $xpath) {
            $value = (string)$this->getValue($xpath);
            if ($value !== '') {
                $theme[$attribute] = $value;
            }
        }

        return $theme;
    }
}
