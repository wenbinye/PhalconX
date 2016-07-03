<?php
namespace PhalconX\Di\Definition;

use Phalcon\DiInterface;
use InvalidArgumentException;

class AliasDefinition extends AbstractDefinition
{
    public function resolve($parameters = null, DiInterface $container = null)
    {
        return $container->get($this->definition);
    }
}
