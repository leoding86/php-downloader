<?php namespace leoding86\Downloader;

class Proxy
{
    protected $proxies = [];

    public function add($protocol, $proxy)
    {
        if ($this->isValidProtocols($protocol)) {
            $this->proxies[$protocol] = $proxy;
        }

        return $this;
    }

    public function setBundle($bundle)
    {
        foreach ($bundle as $protocol => $proxy) {
            $this->add($protocol, $proxy);
        }

        return $this;
    }

    public function toArray()
    {
        return $this->proxies;
    }

    protected function validProtocols()
    {
        return ['http', 'https'];
    }

    protected function isValidProtocols($protocol)
    {
        return in_array($protocol, $this->validProtocols());
    }
}
