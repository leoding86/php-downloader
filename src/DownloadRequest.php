<?php namespace leoding86\Downloader;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;

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
    const ERROR_EVENT = 99;
    const REQUEST_FAILED_ERROR = 100;
    const FILE_TOO_SMALL_ERROR = 101;
    const GET_FILE_SIZE_FAILED_ERROR = 102;
    const CANNOT_REQUEST_FILE_SIZE_ERROR = 103;
    const REQUEST_FILE_FAILED_ERROR = 104;

    protected $resource; // 资源地址

    protected $fileSize; // 资源文件大小

    protected $chunkSize = 1024 * 1024; // 分片下载大小

    protected $timeout = '27.0'; // 超时

    protected $headers;

    protected $proxy;

    protected $chunkIndex = 0;

    protected $chunkList = [];

    protected $enableStream = true;

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
        if (is_string($this->proxy) && !preg_match('https?', $this->proxy)) {
            $this->enableStream = false;
        }

        $this->requestFileSize();
        $this->requestFile();
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

            $this->dispatchEvent(self::BEFORE_REQUEST_EVENT, [$this, $request]);

            $response = $client->send($request, $requestSettings);
        } catch (ClientException $e) {
            $this->dispatchEvent(self::ERROR_EVENT, [$this, self::REQUEST_FAILED_ERROR]);
            return;
        }

        if (!in_array($response->getStatusCode(), [200, 201])) {
            $this->dispatchEvent(self::ERROR_EVENT, [$this, self::GET_FILE_SIZE_FAILED_ERROR]);
            return;
        }

        $contentLength = $response->getHeader('content-length');

        if (empty($contentLength)) {
            $this->dispatchEvent(self::ERROR_EVENT, [$this, self::CANNOT_REQUEST_FILE_SIZE_ERROR]);
            return;
        }

        $this->fileSize = $contentLength[0];

        if ($this->fileSize <= 0) {
            $this->dispatchEvent(self::ERROR_EVENT, [$this, self::FILE_TOO_SMALL_ERROR]);
            return;
        }

        $this->dispatchEvent(self::REQUEST_FILE_SIZE_EVENT, [$this, $this->fileSize, $response]);

        $this->sliceFileToChunks($this->chunkSize, $this->fileSize);
    }

    public function requestFile()
    {
        if ($this->enableStream) {
            $this->requestFileWithStream();
        } else {
            $this->requestFileWithRangeHeader();
        }
    }

    public function requestFileWithStream()
    {
        $request = $this->getRequest();

        $this->dispatchEvent(self::BEFORE_REQUEST_FILE_EVENT, [$this, $request]);
        
        $client = new Client;

        try {
            $requestSettings = $this->getRequestSettings();

            $this->dispatchEvent(self::BEFORE_REQUEST_EVENT, [$this, $request]);

            $response = $client->send($request, $requestSettings);
        } catch (ClientException $e) {
            $this->dispatchEvent(self::ERROR_EVENT, [$this, self::REQUEST_FILE_FAILED_ERROR]);
            return;
        }

        $body = $response->getBody();
        $downloadedSize = 0;

        while (!$body->eof()) {
            $chunkedFile = $body->read($this->chunkSize);
            $downloadedSize += strlen($chunkedFile);

            $this->dispatchEvent(
                self::READ_CHUNKED_FILE_EVENT,
                [$this, $chunkedFile, $downloadedSize]
            );

            if ($downloadedSize >= $this->fileSize) {
                break;
            }
        }

        $this->dispatchEvent(self::COMPLETE_EVENT, [$this]);
    }

    public function requestFileWithRangeHeader()
    {
        $request = $this->getRequest();

        $this->dispatchEvent(self::BEFORE_REQUEST_FILE_EVENT, [$this, $request]);

        try {
            $requestSettings = $this->getRequestSettings();
            $this->dispatchEvent(self::BEFORE_REQUEST_EVENT, [$this, $request]);
            $downloadedSize = 0;

            while (!$this->eoc()) {
                $chunkedFile = $this->readChunk($request);
                $downloadedSize += strlen($chunkedFile);

                $this->dispatchEvent(
                    self::READ_CHUNKED_FILE_EVENT,
                    [$this, $chunkedFile, $downloadedSize]
                );
            }

            $this->dispatchEvent(self::COMPLETE_EVENT, [$this]);
        } catch (ClientException $e) {
            $this->dispatchEvent(self::ERROR_EVENT, [$this, self::REQUEST_FILE_FAILED_ERROR]);
            return;
        }
    }

    public function requestChunkedFile($request)
    {
        $request = $request->withHeader('Range', $this->offsetChunk($this->chunkIndex)->getHeaderRangeValue());
        $client = new Client;
        return $client->send($request, $this->getRequestSettings())->getBody();
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
}
