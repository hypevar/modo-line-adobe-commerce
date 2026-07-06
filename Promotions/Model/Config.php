<?php
/**
 * Line_Promotions
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Promotions\Model;

use Line\Promotions\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
    ) {}

    /**
     * @inheritDoc
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            ConfigInterface::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }

    /**
     * @inheritDoc
     */
    public function getApiKey(?int $storeId = null): string
    {
        return $this->encryptor->decrypt(
            (string) $this->scopeConfig->getValue(
                ConfigInterface::XML_PATH_API_KEY,
                ScopeInterface::SCOPE_STORE,
                $storeId,
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getEndpoint(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_ENDPOINT,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }

    /**
     * @inheritDoc
     */
    public function getMerchant(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_MERCHANT,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }

    /**
     * @inheritDoc
     */
    public function getMerchantId(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_MERCHANT_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }
}
