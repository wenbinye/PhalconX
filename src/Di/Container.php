<?php
namespace PhalconX\Di;

use Interop\Container\ContainerInterface;
use Phalcon\DiInterface;

class Container implements ContainerInterface
{
    private $di;
    
    public function __construct(DiInterface $di)
    {
        $this->di = $di;
    }

    public function get($id)
    {
        return $this->di->get($id);
    }

    public function has($id)
    {
        return $this->di->has($id);
    }
}
