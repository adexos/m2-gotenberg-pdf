<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Test\Unit\Services;

use Adexos\Gotenberg\Model\Config;
use Adexos\Gotenberg\Services\GotenbergPdfService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class GotenbergPdfServiceTest extends TestCase
{
    #[DataProvider('sourceBaseUrlProvider')]
    public function testSourceBaseUrlReplacesSchemeAndHostOnly(string $baseUrl, string $expected): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getSourceBaseUrl')->willReturn($baseUrl);

        $service = (new \ReflectionClass(GotenbergPdfService::class))->newInstanceWithoutConstructor();
        $property = new \ReflectionProperty(GotenbergPdfService::class, 'config');
        $property->setValue($service, $config);

        $method = new ReflectionMethod(GotenbergPdfService::class, 'applySourceBaseUrl');

        $this->assertSame(
            $expected,
            $method->invoke($service, 'https://www.example.com/preview/index?key=abc&a=1')
        );
    }

    public static function sourceBaseUrlProvider(): array
    {
        return [
            'empty keeps the url' => ['', 'https://www.example.com/preview/index?key=abc&a=1'],
            'host is replaced' => ['https://internal.example.com', 'https://internal.example.com/preview/index?key=abc&a=1'],
            'port is kept' => ['http://internal:8080', 'http://internal:8080/preview/index?key=abc&a=1'],
        ];
    }
}
