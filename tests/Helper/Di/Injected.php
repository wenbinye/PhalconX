<?php
namespace PhalconX\Helper\Di;

use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;

class Injected implements InjectionAwareInterface
{
    public $di;
    
    public function setDi(DiInterface $di)
    {
        $this->di = $di;
    }

    public function getDi()
    {
        return $this->di;
    }
}