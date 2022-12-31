<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Test\Unit\Services\Security;

use Adexos\Gotenberg\Model\Config;
use Adexos\Gotenberg\Services\Security\SecureUrlConverter;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RequestInterfaceFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SecureUrlConverterTest extends TestCase
{
    private ?RequestInterfaceFactory $requestFactoryMock = null;

    private ?SecureUrlConverter $secureUrlConverter = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configMock = $this->createMock(Config::class);
        $configMock->method('getEncryptionKey')->willReturn(
            'def00000d6f54eff6d7960eaed4218635a4efeed4ff3136c52cb3c83a2de3fbdd3dcbea86898732ba6f048615795dd0fc27680769c8bb467c3e1d1c1d957014aab14bf4b'
        );

        $this->requestFactoryMock = $this->createMock(RequestInterfaceFactory::class);

        $urlMock = $this->createMock(UrlInterface::class);
        $urlMock->method('getUrl')->willReturnCallback(function (string $url, array $routeParams): string {
            foreach ($routeParams['_query'] as $routeParamKey => $routeParam) {
                $url .= sprintf('/%s/%s', $routeParamKey, $routeParam);
            }

            return $url;
        });

        $this->secureUrlConverter = new SecureUrlConverter(
            $urlMock,
            $this->requestFactoryMock,
            $this->createMock(LoggerInterface::class),
            $configMock
        );
    }

    public function testEncodeParamThenDecoded(): void
    {
        $encodedUrl = $this->secureUrlConverter->encode('https://adx.test', [
            'scope_id' => 5
        ]);

        $requestMock = $this->createStub(RequestInterface::class);
        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('key')
            ->willReturn(explode('/', $encodedUrl)[4]);

        $this->requestFactoryMock->method('create')->willReturn($requestMock);

        self::assertEqualsCanonicalizing(['scope_id' => 5], $this->secureUrlConverter->decodeParams($encodedUrl));
    }

    public function testDecodeParams(): void
    {
        $requestMock = $this->createStub(RequestInterface::class);
        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('key')
            ->willReturn(
                'ZGVmNTAyMDBiZGNjYzUwNThjMzYzNjUwYjM3NDdlMzVlYzQ0NjcxYjNjZDc4MjY5N2ZiNzAxM2FlYjQ4NDQwOTA3MGIxN2M4Zjc2Y2JhMTU0NGM0MWZlMWEwOGMwZDdmOGE0ZmEzMjNlMTNiZDcyZDJiNzFkZTcwMDRlYWMyNDgzNGE2ZmVhMDVjOGJkMjg4YTRjNjM1M2ZkMWJkZjJlN2E5MWRiNDNiY2QzZDc2NDAxOTY1MWJmMzg3MWFjZjhkYzc2NA=='
            );

        $this->requestFactoryMock->method('create')->willReturn($requestMock);

        self::assertEqualsCanonicalizing(['scope_id' => 5], $this->secureUrlConverter->decodeParams(''));
    }
}
