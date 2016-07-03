<?php
namespace PhalconX\Di\Definition;

use Phalcon\DiInterface;

class ValueDefinition extends AbstractDefinition
{
    public function resolve($parameters = null, DiInterface $container = null)
    {
        return $this->definition;
    }
}
