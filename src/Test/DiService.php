<?php
namespace PhalconX\Test;

use PhalconX\Di\Injectable;

trait DiService
{
    use Injectable;

    private $services = [];
    
    public function replaceService($service, $def)
    {
        $di = $this->getDi();
        $this->services[$service] = $di->getService($service);
        $di->set($service, $def);
    }

    public function restoreServices()
    {
        $di = $this->getDi();
        foreach ($this->services as $service => $def) {
            $di->setRaw($service, $def);
        }
    }
}
