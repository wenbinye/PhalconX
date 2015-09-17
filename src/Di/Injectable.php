<?php
namespace PhalconX\Di;

use Phalcon\DiInterface;
use Phalcon\Di;

trait Injectable
{
    private $dependencyInjector;

    public function getDi()
    {
        if (!$this->dependencyInjector) {
            $this->dependencyInjector = Di::getDefault();
        }
        return $this->dependencyInjector;
    }

    public function setDi(DiInterface $di)
    {
        $this->dependencyInjector = $di;
    }

    public function __get($property)
    {
        $di = $this->getDi();
        if ($di->has($property)) {
            return $this->$property = $di->getShared($property);
        }
        if ($property == 'di') {
            return $this->di = $di;
        }
        return null;
    }
}
