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

        $chunkLength = 1024;

        for ($wroteLength = 0, $size = 0; $wroteLength < $fileSize; $wroteLength += $size) {
            if (($size = $fileSize - $wroteLength) > $chunkLength) {
                $size = $chunkLength;
            }

            if (false === fwrite($file, str_repeat(0, $size))) {
                throw new Exception('Cannot fill data to temp file');
            }
        }

        if (false === rewind($file)) {
            throw new Exception('Cannot rewind pointer of temp file to beginning');
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
