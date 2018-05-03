<?php namespace leoding86\Downloader\Traits;

trait MagicGetter
{
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            $getPropertyMethod = 'get' . ucfirst($property);

            if (method_exists($this, $getPropertyMethod)) {
                return $this->$getPropertyMethod();
            } else {
                return $this->$property;
            }
        }
    }
}
