<?php namespace leoding86\Downloader\Event\DownloadRequest;

use leoding86\Downloader\DownloadRequest;
use leoding86\Downloader\Event\AbstractEvent;

class RetryRetrieveChunkedDataEvent extends AbstractEvent
{
    public function dispatch(DownloadRequest $instance, $retryTime)
    {
        $this->dispatchListeners([$instance, $retryTime]);
    }
}
