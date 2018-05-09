<?php namespace leoding86\Downloader\Event\DownloadRequest;

use leoding86\Downloader\DownloadRequest;
use leoding86\Downloader\Event\AbstractEvent;

class RetrieveFileSizeEvent extends AbstractEvent
{
    public function dispatch(DownloadRequest $instance, $fileSize, $response)
    {
        $this->dispatchListeners([$instance, $fileSize, $response]);
    }
}
