<?php namespace leoding86\Downloader;

class FileChunk
{
    use Traits\MagicGetter;

    protected $start;

    protected $end;

    public function __construct($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getHeaderRangeValue()
    {
        return 'bytes=' . (string) $this;
    }

    public function __toString()
    {
        return $this->start . '-' . $this->end;
    }
}
