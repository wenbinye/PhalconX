<?php
namespace PhalconX\Di\Definition\Resolver;

interface DefinitionResolverInterface
{
    /**
     * @param string $name
     * @return \PhalconX\Di\DefinitionInterface
     */
    public function resolve($name);

    /**
     * @param string $name
     * @return bool
     */
    public function isResolvable($name);
}
