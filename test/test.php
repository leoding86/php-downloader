<?php
require __DIR__ . '/../vendor/autoload.php';

use leoding86\Downloader\DownloadTask;
use leoding86\Downloader\DownloadRequest;

$downloadRequest = new DownloadRequest;
$downloadRequest->resource = 'http://185.38.13.159//mp43/263945.mp4?st=dkMzKbBBJKNs0XSCrM7QOw&e=1525343101';
$downloadRequest->chunkSize = 1024 * 1024;
$downloadRequest->requestFileSize();
