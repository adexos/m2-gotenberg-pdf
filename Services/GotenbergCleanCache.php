<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Services;


use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;

class GotenbergCleanCache
{
    private File $driverFile;

    private \Magento\Framework\Filesystem\DirectoryList $directoryList;

    public function __construct(
        File $driverFile,
        \Magento\Framework\Filesystem\DirectoryList $directoryList
    ) {
        $this->driverFile = $driverFile;
        $this->directoryList = $directoryList;
    }

    public function cleanFileCache(string $directory, string $file)
    {
        $directoryMedia = $this->directoryList->getPath(DirectoryList::MEDIA);

        $filePath = sprintf('%s/%s/%s', $directoryMedia, $directory, $file);
        if ($this->driverFile->isExists($filePath)) {
            $this->driverFile->deleteFile($filePath);
        }
    }
}
