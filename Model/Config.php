<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Model;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Config
{
    public const XML_PATH_SERVER_ENDPOINT_GOTENBERG_PDF = 'gotenberg/configuration/endpoint';
    public const XML_PATH_ENCRYPTION_KEY_GOTENBERG_PDF = 'gotenberg/configuration/encryption_key';

    private ScopeConfigInterface $scopeConfig;

    private WriterInterface $configWriter;

    private EncryptorInterface $encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
    }

    public function getEndpoint(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SERVER_ENDPOINT_GOTENBERG_PDF);
    }


    /**
     * @throws EnvironmentIsBrokenException
     */
    public function getEncryptionKey(): string
    {
        if ($result = $this->scopeConfig->getValue(self::XML_PATH_ENCRYPTION_KEY_GOTENBERG_PDF)) {
            return $result;
        }

        $key = (Key::createNewRandomKey())->saveToAsciiSafeString();

        $this->configWriter->save(
            self::XML_PATH_ENCRYPTION_KEY_GOTENBERG_PDF,
            $this->encryptor->encrypt($key)
        );

        return $key;
    }
}
