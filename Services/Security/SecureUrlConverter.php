<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Services\Security;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Exception;
use JsonException;
use Magento\Framework\App\RequestInterfaceFactory;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class SecureUrlConverter
{
    private UrlInterface $url;

    private RequestInterfaceFactory $requestFactory;

    private LoggerInterface $logger;

    public function __construct(
        UrlInterface $url,
        RequestInterfaceFactory $requestFactory,
        LoggerInterface $logger
    ) {
        $this->url = $url;
        $this->requestFactory = $requestFactory;
        $this->logger = $logger;
    }

    /**
     * Useful method to encode URL params that are passed to the request
     *
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws JsonException
     */
    public function encode(string $relativeUrl, array $routeParams): string
    {
        $key = $this->getEncryptionKey();

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
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function decodeParams(string $url): array
    {
        $request = $this->requestFactory->create(['url' => $url]);

        $key = $this->getEncryptionKey();

        try {
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

    /**
     * @return false|string
     * @throws EnvironmentIsBrokenException
     */
    private function getEncryptionKey()
    {
        $storedKeyPath = sprintf('%s/gotenberg_key.txt', sys_get_temp_dir());

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $key = @file_get_contents($storedKeyPath);

        if (!$key) {
            file_put_contents(
                $storedKeyPath,
                (Key::createNewRandomKey())->saveToAsciiSafeString()
            );

            $key = file_get_contents($storedKeyPath);
        }

        return $key;
    }
}
