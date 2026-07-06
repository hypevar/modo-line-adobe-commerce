<?php
/**
 * Line_Promotions
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Promotions\Model\Api;

use Line\Promotions\Api\ConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

class ApiClient
{
    private const HTTP_STATUS_OK = 200;

    /**
     * @param ConfigInterface $config
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly Curl $curl,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Fetch active promotions from the Line API
     *
     * Builds the URL from configuration, authenticates via ApiKey header,
     * and returns the decoded JSON response body as an associative array.
     *
     * @return array<string, mixed>
     * @throws LocalizedException
     */
    public function getActivePromotions(): array
    {
        if (!$this->config->isEnabled()) {
            throw new LocalizedException(
                new Phrase('Line Promotions module is disabled.')
            );
        }

        $url = $this->buildUrl();

        $this->logger->info('Line Promotions: fetching active promotions', ['url' => $url]);

        $this->curl->addHeader('Authorization', 'ApiKey ' . $this->config->getApiKey());
        
        $this->curl->get($url);

        $statusCode = $this->curl->getStatus();

        if ($statusCode !== self::HTTP_STATUS_OK) {
            $this->logger->error(
                'Line Promotions: unexpected API response status',
                [
                    'url'    => $url,
                    'status' => $statusCode,
                    'body'   => $this->curl->getBody(),
                ]
            );

            throw new LocalizedException(
                new Phrase(
                    'Line API returned unexpected status code %1 for URL: %2',
                    [$statusCode, $url],
                )
            );
        }

        $response = json_decode($this->curl->getBody(), true);

        if (!is_array($response)) {
            $this->logger->error(
                'Line Promotions: invalid JSON response',
                ['body' => $this->curl->getBody()]
            );

            throw new LocalizedException(
                new Phrase('Line API returned an invalid or non-JSON response.')
            );
        }

        $this->logger->info('Line Promotions: promotions fetched successfully');

        return $response;
    }

    /**
     * Build the full API endpoint URL
     *
     * Pattern: {endpoint}/payment/marketplace/{merchant}/{merchant_id}/status/active
     *
     * @return string
     */
    private function buildUrl(): string
    {
        return sprintf(
            '%s/payment/marketplace/%s/%s/status/active',
            rtrim($this->config->getEndpoint(), '/'),
            $this->config->getMerchant(),
            $this->config->getMerchantId(),
        );
    }
}
