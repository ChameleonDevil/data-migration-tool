<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Migration\App\Progress;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Class File
 */
class File
{
    /**
     * @var string
     */
    protected $lockFileName = 'migration-tool-progress.lock';

    /**
     * @var \Magento\Framework\Filesystem\DriverInterface
     */
    protected $filesystemDriver;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param \Magento\Framework\Filesystem\Driver\File $filesystemDriver
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $filesystemDriver,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->filesystemDriver = $filesystemDriver;
        $this->filesystem = $filesystem;
    }

    /**
     * Load progress from serialized file
     * @return bool|array
     */
    public function getData()
    {
        if (empty($this->data)) {
            $fileContents = $this->filesystemDriver->fileGetContents($this->getLockFile());
            $isJson = (strpos($fileContents, '{') === 0);
            
            if ($isJson) {
                $data = json_decode($fileContents, true);
            } else {
                //Convert file to JSON format
                $data = @unserialize($fileContents);
                
                if (is_array($data)) {
                    $this->saveData($data);
                }
            }
            
            if (is_array($data)) {
                $this->data = $data;
            }
        }
        return $this->data;
    }

    /**
     * Writing data to lock file
     *
     * @param array $data
     * @return bool
     */
    public function saveData($data)
    {
        if ($this->filesystemDriver->isExists($this->getLockFile())) {
            $this->filesystemDriver->filePutContents($this->getLockFile(), json_encode($data));
            $this->data = $data;
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    protected function getLockFile()
    {
        $lockFileDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath();
        $lockFile = $lockFileDir . DIRECTORY_SEPARATOR . $this->lockFileName;
        if (!$this->filesystemDriver->isExists($lockFile)) {
            $this->filesystemDriver->filePutContents($lockFile, 0);
        }
        return $lockFile;
    }

    /**
     * @return $this
     */
    public function clearLockFile()
    {
        $this->saveData([]);
        return $this;
    }
}
