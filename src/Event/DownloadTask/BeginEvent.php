<?php namespace leoding86\Downloader\Event\DownloadTask;

use leoding86\Downloader\DownloadTask;
use leoding86\Downloader\Event\AbstractEvent;

class BeginEvent extends AbstractEvent
{
    public function dispatch(DownloadTask $instance, $fileSize)
    {
        $this->dispatchListeners([$instance, $fileSize]);
    }
}
