<?php
namespace PhalconX\Di\Definition\Resolver;

class EnvDefinitionResolver implements DefinitionResolverInterface
{
    /**
     * Search the different places for environment variables and return first value found.
     *
     * @param string $name
     * @return string
     */
    public static function findEnvironmentVariable($name)
    {
        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        } elseif (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        } else {
            return getenv($name);
        }
    }

    /**
     * @inheritDoc
     */
    public function isResolvable($name)
    {
        return self::findEnvironmentVariable($name) !== false;
    }

    /**
     * @inheritDoc
     */
    public function resolve($name)
    {
        return new EnvDefinition($name, $name);
    }
}
