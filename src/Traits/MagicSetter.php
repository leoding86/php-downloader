<?php namespace leoding86\Downloader\Traits;

trait MagicSetter
{
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $setPropertyMethod = 'set' . ucfirst($property);

            if (method_exists($this, $setPropertyMethod)) {
                $this->$setPropertyMethod($value);
            } else {
                $this->$property = $value;
            }
        }
    }
}
