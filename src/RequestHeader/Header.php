<?php namespace leoding86\Downloader\RequestHeader;

class Header
{
    protected $headers;

    public function __construct()
    {
        $this->headers = [
            'User-Agent'        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3409.2 Safari/537.36',
        ];
    }

    public function clear()
    {
        $this->headers = [];
    }

    public function toArray()
    {
        return $this->headers;
    }
}
