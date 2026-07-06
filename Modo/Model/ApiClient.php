<?php
/**
 * Line_Modo
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace Line\Modo\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Line\Modo\Api\ApiClientInterface;
use Line\Modo\Api\ConfigInterface;
use Line\Modo\Exception\ApiException;
use Line\Promotions\Model\CcCodeResolver;

class ApiClient implements ApiClientInterface
{
    /**
     * @param CurlFactory $curlFactory
     * @param ConfigInterface $config
     * @param Json $json
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CcCodeResolver $ccCodeResolver
     */
    public function __construct(
        private readonly CurlFactory $curlFactory,
        private readonly ConfigInterface $config,
        private readonly Json $json,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly CcCodeResolver $ccCodeResolver,
    ) { }

    /**
     * Get Curl client
     *
     * @return Curl
     * @throws LocalizedException
     */
    protected function getCurlClient(): Curl
    {
        $curlClient = $this->curlFactory->create();
        $secret = $this->config->getClientSecret();

        $curlClient->addHeader('X-Api-Key', $secret);

        return $curlClient;
    }


    /**
     * @param string $path
     * @return string
     */
    private function getEndpointUrl(string $path): string
    {
        $baseUrl = $this->config->getUrl();
        return $baseUrl . $path;
    }

    /**
     * @inheritDoc
     */
    public function getPayData(OrderInterface $order)
    {
        $payment = $order->getPayment();
        $curlClient = $this->getCurlClient();
        $curlClient->addHeader('Content-Type', 'application/json');
        $url = $this->getEndpointUrl(ConfigInterface::PAYMENT_REQUEST_ENDPOINT);

        $customerEmail = $order->getCustomerEmail();

        $ccCode = $this->config->getCcCode();
        $codes = $this->ccCodeResolver->resolve();

        if (!empty($codes)) {
            $ccCode = implode('-', $codes);
        }

        $dataPost = $this->json->serialize(
            [
                'amount' => (float)$payment->getAmountOrdered(),
                'currency' => (string) $order->getOrderCurrencyCode(),
                'description' => $order->getRealOrderId(),
                'external_intention_id' => $order->getRealOrderId() . '||' . time(),
                'cc_code' => $ccCode,
            ]
        );

        $body = '';
        try {
            // log post info
            $this->logger->info(
                'Requesting payment data from Line API',
                ['url' => $url, 'request' => $dataPost]
            );

            $curlClient->post($url, $dataPost);
            $body = $curlClient->getBody();

            if ($curlClient->getStatus() < 200 || $curlClient->getStatus() >= 400) {
                throw new ApiException(__(
                    'Invalid status "%1" on Line API service',
                    $curlClient->getStatus()
                ));
            }

            $response = $this->json->unserialize($body);
            if (isset($response['data'])) {
                return $response['data'];
            }
            throw new LocalizedException(__('Invalid response data'));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['body' => $body, 'url' => $url, 'request' => $dataPost]);
            throw new Exception($e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getPayment($paymentId): array
    {
        $curlClient = $this->getCurlClient();
        $curlClient->addHeader('Content-Type', 'application/json');
        $url = $this->getEndpointUrl(sprintf(ConfigInterface::PAYMENT_STATUS_ENDPOINT, $paymentId));

        $body = '';
        try {
            $curlClient->get($url);
            $body = $curlClient->getBody();

            $paymentInfo = $this->json->unserialize($body);
            if (isset($paymentInfo['error']) && !!$paymentInfo['error']) {
                throw new LocalizedException(__($paymentInfo['message']));
            }

            return $paymentInfo['data'];
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['body' => $body, 'paymentId' => $paymentId]);
        }
        return [];
    }

}
