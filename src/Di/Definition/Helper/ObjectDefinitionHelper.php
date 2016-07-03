<?php
namespace PhalconX\Di\Definition\Helper;

use PhalconX\Di\Definition\ObjectDefinition;

class ObjectDefinitionHelper implements DefinitionHelperInterface
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var array
     */
    private $constructorParameters;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var array
     */
    private $methods;

    /**
     * @var string
     */
    private $scope;

    public function __construct($className = null)
    {
        $this->className = $className;
    }

    public function constructor(...$params)
    {
        $this->constructorParameters = $params;
        return $this;
    }

    public function property($property, $value)
    {
        $this->properties[$property] = $value;
        return $this;
    }

    public function method($method, ...$args)
    {
        $this->methods[$method][] = $args;
        return $this;
    }

    public function scope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition($name)
    {
        return (new ObjectDefinition($name, $this->className))
            ->setScope($this->scope)
            ->setConstructorParameters($this->constructorParameters)
            ->setProperties($this->properties)
            ->setMethods($this->methods);
    }
}
