<?php namespace leoding86\Downloader\Event\DownloadRequest;

use leoding86\Downloader\DownloadRequest;
use leoding86\Downloader\Event\AbstractEvent;

class FileDownloadedEvent extends AbstractEvent
{
    public function dispatch(DownloadRequest $instance)
    {
        $this->dispatchListeners([$instance]);
    }
}
