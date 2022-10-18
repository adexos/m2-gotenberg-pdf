<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    public const XML_PATH_SERVER_ENDPOINT_GOTENBERG_PDF = 'gotenberg/configuration/endpoint';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getEndpoint(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SERVER_ENDPOINT_GOTENBERG_PDF);
    }
}
