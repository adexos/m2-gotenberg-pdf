<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Test\Unit\Services;

use Adexos\Gotenberg\Model\Config;
use Adexos\Gotenberg\Services\GotenbergPdfService;
use GuzzleHttp\Psr7\HttpFactory;
use Magento\Framework\Filesystem;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

class GotenbergPdfServiceTest extends TestCase
{
    public function testConvertHtmlPostsTheHtmlAsAnIndexFile(): void
    {
        $captured = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['method' => $method, 'url' => $url, 'body' => $this->readBody($options['body'])];

            return new MockResponse('%PDF-content');
        });

        $factory = new HttpFactory();
        $config = $this->createMock(Config::class);
        $config->method('getEndpoint')->willReturn('http://gotenberg:3000');

        $service = new GotenbergPdfService($config, $this->createMock(Filesystem::class), $this->createMock(LoggerInterface::class));
        $client = new ReflectionProperty(GotenbergPdfService::class, 'client');
        $client->setValue($service, new Psr18Client($httpClient, $factory, $factory));

        $pdf = $service->convertHtml('<p>Hello</p>', [['name' => 'printBackground', 'contents' => 'true']]);

        $this->assertSame('%PDF-content', $pdf);
        $this->assertSame('POST', $captured['method']);
        $this->assertSame('http://gotenberg:3000/forms/chromium/convert/html', $captured['url']);
        $this->assertStringContainsString('name="files"; filename="index.html"', $captured['body']);
        $this->assertStringContainsString('<p>Hello</p>', $captured['body']);
        $this->assertStringContainsString('name="printBackground"', $captured['body']);
    }

    private function readBody(mixed $body): string
    {
        if (is_string($body)) {
            return $body;
        }

        $content = '';
        foreach ($body as $chunk) {
            $content .= $chunk;
        }

        return $content;
    }
}
