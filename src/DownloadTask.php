<?php namespace leoding86\Downloader;

use GuzzleHttp\Client;

class DownloadTask
{
    const BEGIN_EVENT = 1;
    const COMPELET_EVENT = 2;
    const PROGRESS_EVENT = 3;
    const ERROR_EVENT = 4;

    protected $resource; // 资源地址

    protected $fileSize; // 资源文件大小

    protected $chunkSize; // 分片下载大小

    protected $proxy; // 设置代理

    protected $timeout = '10.0'; // 超时

    protected $userAgent; // UA

    protected $events = []; // 事件容器

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $setPropertyMethod = 'set' . ucfirst($property);

            if (method_exists($this, $setPropertyMethod)) {
                $this->$setPropertyMethod($value);
            } else {
                $this->$property = $value;
            }
        }
    }

    public function setChunkSize($size)
    {
        if (!is_int($size)) {
            throw new Exception('Property chunckSize must be a number');
        }
    }
}
