<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Services;

interface PdfServiceInterface
{
    /**
     * @param string $path     Relative to pub/media path.
     * @param string $fileName With extension in it.
     *
     * @return string The path where the file was saved.
     */
    public function convert(
        string $urlToConvert,
        string $path,
        string $fileName,
        array $pdfOptions = []
    ): string;

    public function convertWithoutCreateFile(string $urlToConvert,array $pdfOptions = []): string;

    public function isOnline(): bool;
}
