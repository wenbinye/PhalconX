<?php
namespace PhalconX\Di;

class EnvDefinitionResolver implements DefinitionResolverInterface
{
    /**
     * @inheritDoc
     */
    public function isResolvable($name)
    {
        return isset($_ENV[$name]);
    }

    /**
     * @inheritDoc
     */
    public function resolve($name)
    {
        return new EnvDefinition($name, $name);
    }
}
