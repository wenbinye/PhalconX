<?php
namespace PhalconX\Di;

use Phalcon\DiInterface as PhalconDiInterface;
use Phalcon\Di;

/**
 * This trait allows to access services in the service container
 * by a public property name
 */
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

    public function setDi(PhalconDiInterface $di)
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
        if ($property == 'persistent') {
            return $this->persistent = $di->get('sessionBag', [get_class($this)]);
        }
        trigger_error("Access to undefined property $property");
        return null;
    }
}
