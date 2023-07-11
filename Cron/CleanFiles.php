<?php

declare(strict_types=1);

namespace Adexos\Gotenberg\Cron;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;

class CleanFiles
{
    private array $subMediaDirectories;
    
    private DirectoryList $directoryList;

    private File $driverFile;

    public function __construct(
        array $subMediaDirectories,
        DirectoryList $directoryList,
        File $driverFile
    ) {
        $this->subMediaDirectories = $subMediaDirectories;
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
    }

    public function execute(): void
    {
        $directoryMedia = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $expire = strtotime('-30 DAYS');

        foreach ($this->subMediaDirectories as $subMediaDirectory) {
            $paths = $this->driverFile->search('*.pdf', sprintf('%s/%s', $directoryMedia, $subMediaDirectory));

            foreach ($paths as $path) {
                $fileData = $this->driverFile->stat($path);

                if ($fileData['mtime'] > $expire) {
                    continue;
                }

                $this->driverFile->deleteFile($path);
            }
        }
    }
}
