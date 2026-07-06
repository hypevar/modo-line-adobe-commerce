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
use Line\Promotions\Model\Api\ApiClient;
use Psr\Log\LoggerInterface;

class CcCodeResolver
{
    /**
     * @param ConfigInterface $config
     * @param ApiClient $apiClient
     * @param CcCodeExtractor $ccCodeExtractor
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ApiClient $apiClient,
        private readonly CcCodeExtractor $ccCodeExtractor,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Fetch and return all available ccCode parts from the Line Promotions API
     *
     * Returns an empty array when the module is disabled, when the API call fails,
     * or when no codes are found.
     *
     * @return string[]
     */
    public function resolve(): array
    {
        if (!$this->config->isEnabled()) {
            return [];
        }

        try {
            $response = $this->apiClient->getActivePromotions();
            $codes = $this->ccCodeExtractor->extract($response);

            if (empty($codes)) {
                $this->logger->info('Line Promotions: no ccCodes found in API response');

                return [];
            }

            $this->logger->info('Line Promotions: ccCodes resolved', ['codes' => $codes]);

            return $codes;
        } catch (\Throwable $e) {
            $this->logger->error(
                'Line Promotions: failed to resolve ccCodes',
                ['error' => $e->getMessage()]
            );

            return [];
        }
    }
}
