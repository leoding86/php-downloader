<?php namespace leoding86\Downloader;

class DownloadTask
{
    use Traits\MagicSetter;

    const BEGIN_EVENT = 1;
    const COMPELET_EVENT = 2;
    const PROGRESS_EVENT = 3;
    const ERROR_EVENT = 4;

    protected $resource; // 资源地址

    protected $fileSize; // 资源文件大小

    protected $chunkSize; // 分片下载大小

    protected $proxy; // 设置代理

    protected $timeout = '10.0'; // 超时

    protected $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3409.2 Safari/537.36'; // UA

    protected $events = []; // 事件容器

    public function setChunkSize($size)
    {
        if (!is_int($size)) {
            throw new Exception('Property chunckSize must be a number');
        }
    }

    public function start()
    {

    }
}
