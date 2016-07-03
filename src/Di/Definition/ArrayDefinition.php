<?php
namespace PhalconX\Di\Definition;

use Phalcon\DiInterface;
use PhalconX\Di\Definition\Helper\DefinitionHelperInterface;

class ArrayDefinition extends AbstractDefinition
{
    private $definitions;

    private function getDefinitions()
    {
        if ($this->definitions === null) {
            $definitions = [];
            $prefix = $this->getName();
            foreach ($this->definition as $key => $val) {
                $name = "{$prefix}[{$key}]";
                if ($val instanceof DefinitionHelperInterface) {
                    $definitions[$key] = $val->getDefinition($name);
                } elseif (is_array($val)) {
                    $definitions[$key] = new ArrayDefinition($name, $val);
                } else {
                    $definitions[$key] = new ValueDefinition($name, $val);
                }
            }
            $this->definitions = $definitions;
        }
        return $this->definitions;
    }

    public function resolve($parameters = null, DiInterface $container = null)
    {
        $values = [];
        foreach ($this->getDefinitions() as $key => $definition) {
            $values[$key] = $definition->resolve(null, $container);
        }
        return $values;
    }
}
