<?php namespace leoding86\Downloader\Event\DownloadTask;

use leoding86\Downloader\DownloadTask;
use leoding86\Downloader\Event\AbstractEvent;

class ErrorEvent extends AbstractEvent
{
    public function dispatch(DownloadTask $instance)
    {
        //
    }
}
