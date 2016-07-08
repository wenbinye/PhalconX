<?php
namespace PhalconX\Di\Definition\Resolver;

use PhalconX\Di\Definition\ObjectDefinition;

/**
 * @author Ye Wenbin<yewenbin@phoenixos.com>
 */
class ObjectDefinitionResolver implements DefinitionResolverInterface
{
    const CLASS_NAME_REGEX = '/^([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\)*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';
    /**
     * @inheritDoc
     */
    public function isResolvable($name)
    {
        return preg_match(self::CLASS_NAME_REGEX, $name) && class_exists($name);
    }

    /**
     * @inheritDoc
     */
    public function resolve($name, $scope)
    {
        return (new ObjectDefinition($name, $name))
            ->setScope($scope);
    }
}
