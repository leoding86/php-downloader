<?php namespace leoding86\Downloader;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use leoding86\Downloader\Exception\RequestException;
use leoding86\Downloader\Event\EventFactory;

class DownloadRequest
{
    use Traits\MagicSetter;
    use Traits\MagicGetter;
    use Traits\ListenEventAbility;

    const BEFORE_REQUEST_EVENT = 1;
    const BEFORE_REQUEST_FILE_EVENT = 2;
    const READ_CHUNKED_FILE_EVENT = 3;
    const REQUEST_FILE_SIZE_EVENT = 4;
    const COMPLETE_EVENT = 5;
    const RETRY_READ_CHUNK_EVENT = 6;
    const REQUEST_FAILED_ERROR = 100;
    const FILE_TOO_SMALL_ERROR = 101;
    const GET_FILE_SIZE_FAILED_ERROR = 102;
    const CANNOT_GET_FILE_SIZE_ERROR = 103;
    const REQUEST_FILE_FAILED_ERROR = 104;
    const FILE_NOT_EXISTS_ERROR = 105;

    /**
     * Before request event
     *
     * @var \leoding86\Downloader\Event\DownloadRequest\BeforeRequestEvent
     */
    public $beforeRequestEvent;

    /**
     * Before request event
     *
     * @var \leoding86\Downloader\Event\DownloadRequest\RetrieveChunkedDataEvent
     */
    public $retrieveChunkedDataEvent;

    /**
     * Before request event
     *
     * @var \leoding86\Downloader\Event\DownloadRequest\FileDownloadedEvent
     */
    public $fileDownloadedEvent;

    /**
     * Before request event
     *
     * @var \leoding86\Downloader\Event\DownloadRequest\RetrieveFileSizeEvent
     */
    public $retrieveFileSizeEvent;

    /**
     * Before request event
     *
     * @var \leoding86\Downloader\Event\DownloadRequest\RetryRetrieveChunkedDataEvent
     */
    public $retryRetrieveChunkedDataEvent;

    protected $resource; // 资源地址

    protected $fileSize; // 资源文件大小

    protected $chunkSize = 1024 * 1024; // 分片下载大小

    protected $timeout = '27.0'; // 超时

    protected $headers;

    protected $proxy;

    protected $chunkIndex = 0;

    protected $chunkList = [];

    protected $enableStream = true;

    protected $chunkRetry = 5;

    protected $retryChunkInterval = 300;

    protected $skipDownload = false;

    public function __construct()
    {
        $this->beforeRequestEvent = EventFactory::create('DownloadRequest\\BeforeRequestEvent');
        $this->retrieveChunkedDataEvent = EventFactory::create('DownloadRequest\\RetrieveChunkedDataEvent');
        $this->fileDownloadedEvent = EventFactory::create('DownloadRequest\\FileDownloadedEvent');
        $this->retrieveFileSizeEvent = EventFactory::create('DownloadRequest\\RetrieveFileSizeEvent');
        $this->retryRetrieveChunkedDataEvent = EventFactory::create('DownloadRequest\\RetryRetrieveChunkedDataEvent');
    }

    public function setChunkSize($size)
    {
        if (!is_int($size)) {
            throw new Exception('Property chunkSize must be a number');
        }

        $this->chunkSize = $size;
    }

    public function setHeaders(RequestHeader\Header $header)
    {
        $this->headers = $header->toArray();
    }

    public function getHeaders()
    {
        if (is_null($this->headers)) {
            $headers = new RequestHeader\Header;
            $this->headers = $headers->toArray();
        }

        return $this->headers;
    }

    public function begin()
    {
        $this->requestFileSize();

        if ($this->skipDownload) {
            return;
        }

        $this->requestFile();
    }

    public function skipDownload()
    {
        $this->skipDownload = true;
    }

    public function getRequest($requestType = 'GET')
    {
        $request = new Request($requestType, $this->resource, $this->getHeaders());

        return $request;
    }

    public function requestFileSize()
    {
        if ($this->enableStream) {
            $request = $this->getRequest();
        } else {
            $request = $this->getRequest('HEAD');
        }

        $client = new Client;
        
        try {
            $requestSettings = $this->getRequestSettings();

            $this->beforeRequestEvent->dispatch($this, $request);

            $response = $client->send($request, $requestSettings);
        } catch (ClientException $e) {
            if ($e->getCode() >= 400 && $e->getCode() <= 499) {
                throw new RequestException(null, self::FILE_NOT_EXISTS_ERROR, $e);
            }
            throw new RequestException(null, self::REQUEST_FAILED_ERROR, $e);
        }

        $contentLength = $response->getHeader('content-length');

        if (empty($contentLength)) {
            throw new RequestException(null, self::CANNOT_GET_FILE_SIZE_ERROR);
        }

        $this->fileSize = $contentLength[0];

        if ($this->fileSize <= 0) {
            throw new RequestExcetpion(null, self::FILE_TOO_SMALL_ERROR);
        }

        $this->retrieveFileSizeEvent->dispatch($this, $this->fileSize, $response);

        $this->sliceFileToChunks($this->chunkSize, $this->fileSize);
    }

    public function requestFile()
    {
        if ($this->enableStream) {
            $this->requestFileWithStream();
        } else {
            $this->requestFileWithRangeHeader();
        }

        $this->fileDownloadedEvent->dispatch($this);
    }

    public function requestFileWithStream()
    {
        $request = $this->getRequest();
        $client = new Client;

        try {
            $requestSettings = $this->getRequestSettings();

            $this->beforeRequestEvent->dispatch($this, $request);

            $response = $client->send($request, $requestSettings);
        } catch (ClientException $e) {
            throw new RequestException(null, self::REQUEST_FILE_FAILED_ERROR);
        }

        $body = $response->getBody();
        $downloadedSize = 0;

        while (!$body->eof()) {
            $chunkedData = $body->read($this->chunkSize);
            $downloadedSize += strlen($chunkedData);

            /* Dispatch retrieve chunked file data event */
            $this->retrieveChunkedDataEvent->dispatch($this, $chunkedData, $downloadedSize);

            if ($downloadedSize >= $this->fileSize) {
                break;
            }
        }
    }

    public function requestFileWithRangeHeader()
    {
        $request = $this->getRequest();

        try {
            $requestSettings = $this->getRequestSettings();

            /* Dispatch before quest event */
            $this->beforeRequestEvent->dispatch($this, $request);
            
            $downloadedSize = 0;

            while (!$this->eoc()) {
                $chunkedData = $this->readChunk($request);
                $downloadedSize += strlen($chunkedData);

                $this->retrieveChunkedDataEvent->dispatch($this, $chunkedData, $downloadedSize);
            }
        } catch (ClientException $e) {
            throw new RequestException(null, self::REQUEST_FILE_FAILED_ERROR);
        }
    }

    public function requestChunkedFile($request)
    {
        $retry = 0;
        $request = $request->withHeader('Range', $this->offsetChunk($this->chunkIndex)->getHeaderRangeValue());
        $client = new Client;
        $lastException = null;

        while (!$this->reachChunkRetryLimit($retry++)) {
            try {
                usleep($this->retryChunkInterval);
                $chunkData = $client->send($request, $this->getRequestSettings())->getBody();
                return $chunkData;
            } catch (Exception $e) {
                $this->retryRetrieveChunkedDataEvent->dispatch($this, $retry);
                $lastException = $e;
            }
        }

        throw $lastException;
    }

    public function getRequestSettings()
    {
        $settings = [];

        if ($this->enableStream) {
            $settings['stream'] = true;
        }

        if ($this->timeout > 0) {
            $settings['timeout'] = $this->timeout;
        }

        if (!is_null($this->proxy)) {
            $settings['proxy'] = $this->proxy;
        }
        
        /**
         * stream模式不支持非socks5代理
         */
        if (is_string($this->proxy) && !preg_match('/https?/', $this->proxy)) {
            $settings['stream'] = false;
        }

        return $settings;
    }

    public function readChunk($request)
    {
        $chunkData = $this->requestChunkedFile($request);
        $this->chunkIndex++;

        usleep(300);

        return $chunkData;
    }

    public function reset()
    {
        $this->chunkIndex = 0;
    }

    public function eoc()
    {
        return $this->chunkIndex >= count($this->chunkList);
    }

    /**
     * @return FileChunk
     */
    public function offsetChunk($offset)
    {
        return $this->chunkList[$offset];
    }

    public function appendChunk($start, $end)
    {
        $this->chunkList[] = new FileChunk($start, $end);
    }

    protected function sliceFileToChunks($chunkSize, $fileSize)
    {
        if ($chunkSize <= 0) {
            $this->appendChunk(0, $fileSize);
            return;
        }

        for ($start = 0; $fileSize >= $start;) {
            $end = $start + $this->chunkSize;

            if ($end > $fileSize) {
                $end = $fileSize;
            }

            $this->appendChunk($start, $end);

            $start = $end + 1;
        }
    }

    protected function reachChunkRetryLimit($retry)
    {
        return $retry >= $this->chunkRetry;
    }
}
