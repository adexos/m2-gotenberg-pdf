<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Services;

use Adexos\Gotenberg\Exception\GotenbergConvertException;
use Adexos\Gotenberg\Model\Config;
use Adexos\Gotenberg\Model\LoggerWrapper;
use Exception;
use GuzzleHttp\Psr7\MultipartStream;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

class GotenbergPdfService implements PdfServiceInterface
{
    private const GOTENBERG_CHROMIUM_ENDPOINT = 'forms/chromium/convert/url';

    private ?ClientInterface $client = null;

    private Config $config;

    private Filesystem $filesystem;

    private LoggerInterface $logger;

    public function __construct(
        Config $config,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * @param array{
     *     array{
     *      name: string,
     *      contents: string
     *    }[]
     *  } | array[] $pdfOptions
     *
     * List of available options can be found here https://gotenberg.dev/docs/modules/chromium#routes
     *
     * @throws ClientExceptionInterface
     * @throws GotenbergConvertException
     * @throws FileSystemException
     */
    public function convert(
        string $urlToConvert,
        string $path,
        string $fileName,
        array $pdfOptions = []
    ): string {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

        $relativePath = sprintf('%s/%s', $path, $fileName);
        $absolutePath = $directory->getAbsolutePath($relativePath);

        $this->logger->debug(
            '[ADX-Gotenberg] Processing PDF...',
            ['context' => 'adx_gotenberg', 'url_processed' => $urlToConvert, 'pdf_path' => $absolutePath]
        );

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        if (@file_get_contents($absolutePath) !== false) {
            $this->logger->debug(
                '[ADX-Gotenberg] PDF was not created as it was already cached.',
                ['context' => 'adx_gotenberg', 'url_processed' => $urlToConvert, 'pdf_path' => $absolutePath]
            );

            return $absolutePath;
        }

        $request = $this->prepareRequest($urlToConvert, $pdfOptions);

        try {
            $result = $this->getClient()->sendRequest($request);

            $directory->writeFile($relativePath, $result->getBody()->getContents());
        } catch (Exception $e) {
            $this->logger->error($e, ['context' => 'adx_gotenberg']);

            throw new GotenbergConvertException(__('Failed converting PDF'), $e);
        }

        $this->logger->debug(
            '[ADX-Gotenberg] Successfully created PDF.',
            ['context' => 'adx_gotenberg', 'url_processed' => $urlToConvert, 'pdf_path' => $absolutePath]
        );

        return $absolutePath;
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function isOnline(): bool
    {
        try {
            $request = $this->getClient()->createRequest('GET', $this->config->getEndpoint());

            $response = $this->getClient()->sendRequest($request);

            return $response->getStatusCode() === 404;
        } catch (Exception $e) {
            $this->logger->alert($e, ['context' => 'adx_gotenberg']);

            throw new RuntimeException('Unable to connect to Gotenberg');
        }
    }

    /**
     * @return Psr18Client
     */
    private function getClient(): ClientInterface
    {
        if ($this->client === null) {
            $this->client = new Psr18Client(HttpClient::create());
        }

        return $this->client;
    }

    private function prepareRequest(string $urlToConvert, array $pdfOptions): RequestInterface
    {
        $request = $this->getClient()->createRequest(
            'POST',
            sprintf('%s/%s', $this->config->getEndpoint(), self::GOTENBERG_CHROMIUM_ENDPOINT)
        );

        $body = new MultipartStream([
            ['name' => 'url', 'contents' => $urlToConvert],
            ['name' => 'emulatedMediaType', 'contents' => 'screen'],
            ...$pdfOptions
        ]);

        return $request
            ->withHeader(
                'Content-Type',
                sprintf('multipart/form-data; boundary="%s"', $body->getBoundary())
            )
            ->withBody($body);
    }
}
