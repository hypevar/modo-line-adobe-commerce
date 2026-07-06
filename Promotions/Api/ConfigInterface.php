<?php
/**
 * Line_Promotions
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Promotions\Api;

interface ConfigInterface
{
    public const MODULE_CODE = 'line_promotions';

    public const XML_PATH_ENABLED     = 'payment/line_promotions/enabled';
    public const XML_PATH_API_KEY     = 'payment/line_promotions/api_key';
    public const XML_PATH_ENDPOINT    = 'payment/line_promotions/endpoint';
    public const XML_PATH_MERCHANT    = 'payment/line_promotions/merchant';
    public const XML_PATH_MERCHANT_ID = 'payment/line_promotions/merchant_id';

    /**
     * Check if the module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool;

    /**
     * Get the API key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiKey(?int $storeId = null): string;

    /**
     * Get the API base endpoint URL
     *
     * @param int|null $storeId
     * @return string
     */
    public function getEndpoint(?int $storeId = null): string;

    /**
     * Get the marketplace identifier
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMerchant(?int $storeId = null): string;

    /**
     * Get the marketplace ID
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMerchantId(?int $storeId = null): string;
}
