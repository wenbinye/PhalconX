<?php
namespace PhalconX\Di\Definition\Resolver;

use PhalconX\Di\Definition\ObjectDefinition;

/**
 * @author Ye Wenbin<yewenbin@phoenixos.com>
 */
class ObjectDefinitionResolver implements DefinitionResolverInterface
{
    /**
     * @inheritDoc
     */
    public function isResolvable($name)
    {
        return class_exists($name);
    }

    /**
     * @inheritDoc
     */
    public function resolve($name)
    {
        return new ObjectDefinition($name, $name);
    }
}
