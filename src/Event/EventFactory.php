<?php namespace leoding86\Downloader\Event;

class EventFactory
{
    public static function create($event)
    {
        $class = self::loadEventClass($event);
        return new $class();
    }

    private static function loadEventClass($event)
    {
        if (strpos($event, '\\') === 0) {
            return $event;
        } else {
            return '\\' . __NAMESPACE__ . '\\' . $event;
        }
    }
}
