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

interface ConfigInterface
{
    public const PAYMENT_CODE = 'line_modo';
    public const XPATH_PRODUCTION_ENDPOINT = 'production_endpoint';
    public const XPATH_FRAME_PRODUCTION_ENDPOINT =  'production_frame_endpoint';
    public const XPATH_SANDBOX_ENDPOINT = 'sandbox_endpoint';
    public const XPATH_FRAME_SANDBOX_ENDPOINT = 'sandbox_frame_endpoint';
    public const XPATH_CC_CODE = 'cc_code';
    public const PAYMENT_REQUEST_ENDPOINT = '/payments/request';
    public const PAYMENT_STATUS_ENDPOINT = '/payments/%s/status';
    public const XPATH_DEBUG = 'debug';
    public const XPATH_ENVIRONMENT = 'environment';
    public const XPATH_ORDER_STATUS = 'order_status';
    public const XPATH_PRODUCTION_PUB_KEY = 'production_credentials/pub_key';
    public const XPATH_PRODUCTION_SECRET_KEY = 'production_credentials/secret_key';
    public const XPATH_REDIRECT = 'redirect';
    public const XPATH_SANDBOX_PUB_KEY = 'sandbox_credentials/pub_key';
    public const XPATH_SANDBOX_SECRET_KEY = 'sandbox_credentials/secret_key';
    public const XPATH_NOTIFICATION_EMAIL_STATUS = 'notifications/email_enabled';
    public const XPATH_NOTIFICATION_EMAIL_TEMPLATE = 'notifications/email_notification_template';
    public const XPATH_STATUS_PAY = 'status_pay';
    public const XPATH_STATUS_REJECTED = 'status_rejected';
    public const XPATH_GENERATE_INVOICE = 'generate_invoice';
    public const ORDER_ADDITIONAL_KEY = 'line_data';

    // Theme
    public const XPATH_THEME_PRIMARY_COLOR = 'theme/primary_color';
    public const XPATH_THEME_BACKGROUND_COLOR = 'theme/background_color';
    public const XPATH_THEME_TEXT_COLOR = 'theme/text_color';
    public const XPATH_THEME_BORDER_COLOR = 'theme/border_color';
    public const XPATH_THEME_FONT_FAMILY = 'theme/font_family';
    public const XPATH_THEME_FONT_SIZE = 'theme/font_size';
    public const XPATH_THEME_BORDER_RADIUS = 'theme/border_radius';
    public const XPATH_THEME_BUTTON_BORDER_RADIUS = 'theme/button_border_radius';
    public const XPATH_THEME_BUTTON_TEXT_COLOR = 'theme/button_text_color';
    public const XPATH_THEME_BUTTON_HOVER_COLOR = 'theme/button_hover_color';


    /**
     * @param string|null $environment
     * @return string
     */
    public function getClientSecret(?string $environment = null);

    /**
     * @param string|null $environment
     * @return mixed
     */
    public function getClientPubKey(?string $environment = null);

    /**
     * @return string
     */
    public function getEnvironment(): string;

    /**
     * Return the current notification email status
     *
     * @return string
     */
    public function getNotificationEmailStatus(): string;

    /**
     * @return string
     */
    public function getNotificationEmailTemplate(): string;

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function getOrderKey(OrderInterface $order): string;

    /**
     * @return string
     */
    public function getOrderStatus(): string;

    /**
     * @return string
     */
    public function getStatusPay();

    /**
     * @return string
     */
    public function getStatusRejected();

    /**
     * @return bool
     */
    public function canGenerateInvoice(): bool;


    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @return string
     */
    public function getFrameUrl(): string;

    /**
     * @return bool
     */
    public function isDebugMode(): bool;

    /**
     * @return string
     */
    public function getCcCode(): string;

    /**
     * Returns an array of non-empty theme values keyed by data-attribute name.
     *
     * @return array<string, string>
     */
    public function getTheme(): array;
}
