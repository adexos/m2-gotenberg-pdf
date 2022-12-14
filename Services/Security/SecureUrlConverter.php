<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Services\Security;

use Adexos\Gotenberg\Model\Config;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use Magento\Framework\App\RequestInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class SecureUrlConverter
{
    private UrlInterface $url;

    private RequestInterfaceFactory $requestFactory;

    private LoggerInterface $logger;

    private Config $config;

    public function __construct(
        UrlInterface $url,
        RequestInterfaceFactory $requestFactory,
        LoggerInterface $logger,
        Config $config
    ) {
        $this->url = $url;
        $this->requestFactory = $requestFactory;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Useful method to encode URL params that are passed to the request
     *
     * @throws LocalizedException
     */
    public function encode(string $relativeUrl, array $routeParams): string
    {
        try {
            $key = $this->config->getEncryptionKey();

            return $this->url->getUrl(
                $relativeUrl,
                [
                    '_query' => [
                        'key' => base64_encode(
                            Crypto::encrypt(
                                json_encode($routeParams, JSON_THROW_ON_ERROR),
                                Key::loadFromAsciiSafeString($key)
                            )
                        )
                    ]
                ]
            );
        } catch (Exception $e) {
            throw new LocalizedException(__('Failed to decode secure URL.'), $e);
        }
    }

    public function decodeParams(string $url): array
    {
        $request = $this->requestFactory->create(['url' => $url]);

        try {
            $key = $this->config->getEncryptionKey();

            return json_decode(
                Crypto::decrypt(
                    base64_decode($request->getParam('key', '')),
                    Key::loadFromAsciiSafeString($key)
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (Exception $e) {
            $this->logger->alert('Failed to decode URL key param', ['exception' => $e, 'context' => 'adx_gotenberg']);

            return [];
        }
    }
}
