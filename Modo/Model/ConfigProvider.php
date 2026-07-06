<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace Line\Modo\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;
use Line\Modo\Api\ConfigInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param UrlInterface $urlBuilder
     * @param ConfigInterface $config
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                'line_modo' => [
                    'redirectUrl' => $this->urlBuilder->getUrl('line/modo/start'),
                ]
            ]
        ];
    }
}
