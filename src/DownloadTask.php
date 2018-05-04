<?php namespace leoding86\Downloader;

use Exception;
use leoding86\Downloader\Exception\RequestException;
use leoding86\Downloader\Exception\FileNotExistsException;

class DownloadTask
{
    use Traits\MagicSetter;
    use Traits\MagicGetter;

    protected $resource;

    protected $headers;

    protected $chunkSize = 1024 * 1024 * 5;

    protected $timeout = 27.0;

    protected $filename;

    protected $saveDir;

    protected $proxy;

    protected $enableStream;

    protected $verbose = false;

    private $downloadRequest;

    private $file;

    public function __construct($resource, $saveDir)
    {
        $this->setResource($resource);
        $this->setSaveDir($saveDir);
    }

    public function setResource($resource)
    {
        if (!preg_match('/^https?:\/{2}/', $resource)) {
            throw new Exception('Unsupported resource type');
        }

        $this->resource = $resource;
    }

    public function setChunkSize($size)
    {
        if (!is_int($size)) {
            throw new Exception('Property chunkSize must be a number');
        }

        $this->chunkSize = $size;
    }

    public function setSaveDir($dir)
    {
        if (!is_dir($dir)) {
            throw new Exception('Save dir is invalid');
        }

        $this->saveDir = realpath($dir);
    }

    public function start()
    {
        $this->downloadRequest = new DownloadRequest();
        $this->downloadRequest->resource = $this->resource;
        $this->downloadRequest->chunkSize = $this->chunkSize;
        $this->downloadRequest->timeout = $this->timeout;
        $this->downloadRequest->chunkSize = $this->chunkSize;
        $this->downloadRequest->proxy = $this->proxy;
        $this->downloadRequest->enableStream = $this->enableStream;

        $this->regiesterEventListeners();

        try {
            $this->downloadRequest->begin();
        } catch (RequestException $e) {
            if ($e->getCode() == DownloadRequest::FILE_NOT_EXISTS_ERROR) {
                throw new FileNotExistsException($e->getMessage);
            } else {
                throw $e;
            }
        }
    }

    public function getFilename()
    {
        if (preg_match('/[^\/]+\.[^\/.]+$/', explode('#', $this->resource)[0], $matches)) {
            return $matches[0];
        } else {
            return md5($this->resource);
        }
    }

    public function createFile()
    {
        $file = fopen($this->getFullPath(), 'w+');
        return $file;
    }

    public function getFullPath()
    {
        return $this->saveDir . '/' . $this->filename;
    }

    public function regiesterEventListeners()
    {
        $this->downloadRequest->attachEvent(
            DownloadRequest::REQUEST_FILE_SIZE_EVENT,
            function ($downloadRequest, $fileSize, $response) {
                if (is_null($this->filename)) {
                    if (!empty($contentDisposition = $response->getHeader('Content-Disposition'))
                        && preg_match('/filename="([^"]+)"/', $response->getHeaderLine('Content-Disposition'), $matches)
                    ) {
                        $this->filename = $matches[1];
                    } else {
                        $this->filename = $this->getFilename();
                    }
                }

                $this->file = $this->createFile();

                if ($this->verbose) {
                    printf("File size is $fileSize" . PHP_EOL);
                }
            }
        );

        $this->downloadRequest->attachEvent(
            DownloadRequest::READ_CHUNKED_FILE_EVENT,
            function ($downloadRequest, $chunkedData, $downloadedSize) {
                fwrite($this->file, $chunkedData);

                if ($this->verbose) {
                    printf("Download progress: $downloadedSize / $downloadRequest->fileSize (" . round($downloadedSize * 100 / $downloadRequest->fileSize, 1) . "%%)" . PHP_EOL);
                }
            }
        );

        $this->downloadRequest->attachEvent(
            DownloadRequest::RETRY_READ_CHUNK_EVENT,
            function ($downloadRequest, $retryTime) {
                if ($this->verbose) {
                    printf("Read chunk data failed, retry [retry time: {$retryTime}]" . PHP_EOL);
                }
            }
        );

        $this->downloadRequest->attachEvent(
            DownloadRequest::COMPLETE_EVENT,
            function ($downloadRequest) {
                fclose($this->file);

                if ($this->verbose) {
                    printf("Download complete, save in {$this->getFullPath()}" . PHP_EOL);
                }
            }
        );
    }
}
