<?php
namespace PhalconX\Di\Definition\Helper;

use PhalconX\Di\Definition\FactoryDefinition;

class FactoryDefinitionHelper implements DefinitionHelperInterface
{
    /**
     * @var callable
     */
    private $factory;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var string
     */
    private $scope;

    public function __construct(callable $factory, $arguments)
    {
        $this->factory = $factory;
        $this->arguments = $arguments;
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
        return (new FactoryDefinition($name, $this->factory))
            ->setScope($this->scope)
            ->setArguments($this->arguments);
    }
}
