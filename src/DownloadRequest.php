<?php namespace leoding86\Downloader;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class DownloadRequest
{
    use Traits\MagicSetter;
    use Traits\ListenEventAbility;

    const BEFORE_REQUEST_EVENT = 1;
    const FILE_TOO_SMALL_EVENT = 10;
    const GET_FILE_SIZE_FAILED_EVENT = 11;

    protected $resource; // 资源地址

    protected $fileSize; // 资源文件大小

    protected $chunkSize; // 分片下载大小

    protected $proxy; // 设置代理

    protected $timeout = '10.0'; // 超时

    protected $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3409.2 Safari/537.36'; // UA

    protected $chunkIndex = 0;

    protected $chunkList = [];

    public function setChunkSize($size)
    {
        if (!is_int($size)) {
            throw new Exception('Property chunckSize must be a number');
        }
    }

    public function requestFileSize()
    {
        $request = new Request('GET', $this->resource, $this->buildRequestHeaders());

        $this->dispatchEvent(self::BEFORE_REQUEST_EVENT, [$this, $request]);

        $client = new Client;
        $response = $client->send($request, $this->getRequestSettings());

        if (!in_array($response->getStatusCode(), [200, 201])) {
            $this->dispatchEvent(self::GET_FILE_SIZE_FAILED_EVENT);
            return;
        }

        $this->fileSize = $response->getHeader('content-length');

        if ($this->fileSize <= 0) {
            $this->dispatchEvent(self::FILE_TOO_SMALL_EVENT, [$this]);
            return;
        }

        $this->sliceFileToChunks($this->chunckSize, $this->fileSize);
    }

    public function getRequest()
    {
        $request = new Request('GET', $this->resource, $this->buildRequestHeaders());
        $this->dispatchEvent(self::BEFORE_REQUEST_EVENT, [$this, $request]);

        return $request;
    }

    public function buildRequestHeaders()
    {
        return [];
    }

    public function getRequestSettings()
    {
        return [];
    }

    public function start()
    {
        $this->reset();
        return $this->requestChunkedFile();
    }

    public function next()
    {
        $this->chunkIndex++;
        return $this->requestChunkedFile();
    }

    public function reset()
    {
        $this->chunkIndex = 0;
    }

    public function requestChunkedFile()
    {
        $request = $this->getRequest();
        $request->withHeader('Range', (string) $this->offsetChunk($this->chunkIndex));
        $client = new Client;

        $this->dispatchEvent(self::BEFORE_REQUEST_CHUNKED_FILE_EVENT, [$this, $request]);

        $client->send($request, $this->getRequestSettings());
    }

    /**
     * 
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

        for ($start = 0; $fileSize >= $start; ) {
            $end = $start + $this->chunkSize;

            if ($end > $fileSize) {
                $end = $fileSize;
            }

            $this->appendChunk($start, $end);

            $start += $this->chunkSize + 1;
        }
    }
}
