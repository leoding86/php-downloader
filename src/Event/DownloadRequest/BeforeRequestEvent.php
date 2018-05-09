<?php namespace leoding86\Downloader\Event\DownloadRequest;

use leoding86\Downloader\DownloadRequest;
use leoding86\Downloader\Event\AbstractEvent;

class BeforeRequestEvent extends AbstractEvent
{
    public function dispatch(DownloadRequest $instance, $request)
    {
        $this->dispatchListeners([$instance, $request]);
    }
}
