<?php
namespace PhalconX\Di\Definition;

use Phalcon\DiInterface;

class FactoryDefinition extends AbstractDefinition
{
    /**
     * @var array
     */
    private $arguments;

    /**
     * @var array<DefinitionInterface>
     */
    private $argumentDefinitions;

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    public function resolve($parameters = null, DiInterface $container = null)
    {
        $arguments = [];
        if ($parameters !== null) {
            $arguments = $parameters;
        } elseif (empty($this->arguments)) {
            $arguments = [$container];
        } else {
            foreach ($this->getArgumentDefinitions() as $i => $definition) {
                $arguments[] = $definition->resolve(null, $container);
            }
        }
        return call_user_func_array($this->definition, $arguments);
    }

    private function getArgumentDefinitions()
    {
        if ($this->argumentDefinitions === null) {
            $definitions = [];
            $prefix = $this->getName();
            foreach ($this->getArguments() as $i => $arg) {
                $definitions[] = $this->createDefinition($prefix . ".arg[{$i}]", $arg);
            }
            $this->argumentDefinitions = $definitions;
        }
        return $this->argumentDefinitions;
    }
}
