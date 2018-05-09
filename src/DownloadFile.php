<?php namespace leoding86\Downloader;

use Exception;

class DownloadFile
{
    private $filename;
    private $tempFile;

    public function __construct($filename, $fileSize)
    {
        $this->filename = $filename;
        $this->tempFile = $this->createTempFile($fileSize);
    }

    public function getTempFilename()
    {
        return $this->filename . '.phpdownloading';
    }

    public function createTempFile($fileSize)
    {
        if (false === ($file = @fopen($this->getTempFilename(), 'w+'))) {
            throw new Exception('Cannot open file');
        }
        
        if (false === fwrite($file, str_repeat(0, $fileSize)) ||
            false === rewind($file)
        ) {
            throw new Exception('Cannot fill data to temp file');
        }

        return $file;
    }

    public function writeData($data)
    {
        fwrite($this->tempFile, $data);
    }

    public function complete()
    {
        fclose($this->tempFile);
        rename($this->getTempFilename(), $this->filename);
    }

    public function getSavedFilename()
    {
        return $this->filename;
    }
}
