<?php
namespace PhalconX\Di\Definition\Resolver;

interface DefinitionResolverInterface
{
    /**
     * @param string $name
     * @param string $scope
     * @return \PhalconX\Di\DefinitionInterface
     */
    public function resolve($name, $scope);

    /**
     * @param string $name
     * @return bool
     */
    public function isResolvable($name);
}
