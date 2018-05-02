<?php namespace leoding86\Downloader\Traits;

trait ListenEventAbility
{
    private $events = [];

    public function attachEvent($event, callable $listener)
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        $this->events[$event][] = $listener;
    }

    public function dispatchEvent($event, array $arguments = [])
    {
        if (!isset($this->events[$event])) {
            return;
        }

        foreach ($this->events[$event] as $listener) {
            call_user_func_array($listener, $arguments);
        }
    }
}