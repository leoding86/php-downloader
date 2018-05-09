<?php namespace leoding86\Downloader\Event\DownloadTask;

use leoding86\Downloader\DownloadTask;
use leoding86\Downloader\Event\AbstractEvent;

class CompleteEvent extends AbstractEvent
{
    public function dispatch(DownloadTask $instance)
    {
        $this->dispatchListeners([$instance]);
    }
}
