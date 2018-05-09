<?php namespace leoding86\Downloader\Event\DownloadRequest;

use leoding86\Downloader\DownloadRequest;
use leoding86\Downloader\Event\AbstractEvent;

class RetrieveChunkedDataEvent extends AbstractEvent
{
    public function dispatch(DownloadRequest $instance, $chunkedData, $downloadedSize)
    {
        $this->dispatchListeners([$instance, $chunkedData, $downloadedSize]);
    }
}
