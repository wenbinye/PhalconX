<?php
namespace PhalconX\Di;

use Phalcon\DiInterface;
use Phalcon\Di;

/**
 * This trait allows to access services in the service container
 * by a public property name
 */
trait InjectionAware
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
}
