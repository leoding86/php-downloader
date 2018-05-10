<?php namespace leoding86\Downloader\Event;

use Exception;

abstract class AbstractEvent
{
    protected $listeners = [];

    public function __construct()
    {
        if (!method_exists($this, 'dispatch')) {
            throw new Exception("Must implement method dispatch in child class");
        }
    }

    public function addListener($listener)
    {
        $this->listeners[] = $listener;
    }

    protected function dispatchListeners(array $arguments = [])
    {
        foreach ($this->listeners as $listener) {
            call_user_func_array($listener, $arguments);
        }
    }
}
