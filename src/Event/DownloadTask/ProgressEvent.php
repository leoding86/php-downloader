<?php namespace leoding86\Downloader\Event\DownloadTask;

use leoding86\Downloader\DownloadTask;
use leoding86\Downloader\Event\AbstractEvent;

class ProgressEvent extends AbstractEvent
{
    public function dispatch(DownloadTask $instance, $downloadedSize, $fileSize)
    {
        $this->dispatchListeners([$instance, $downloadedSize, $fileSize]);
    }
}
