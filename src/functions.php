<?php
namespace PhalconX\Di {

    use PhalconX\Di\Definition\Helper\AliasDefinitionHelper;
    use PhalconX\Di\Definition\Helper\ValueDefinitionHelper;
    use PhalconX\Di\Definition\Helper\FactoryDefinitionHelper;
    use PhalconX\Di\Definition\Helper\ArrayDefinitionHelper;
    use PhalconX\Di\Definition\Helper\ObjectDefinitionHelper;
    use PhalconX\Di\Definition\Helper\EnvDefinitionHelper;
    use PhalconX\Di\Definition\Helper\StringDefinitionHelper;

    if (!function_exists('\PhalconX\Di\get')) {
        /**
         * reference another definition
         *
         * @param  string $alias
         * @return DefinitionInterface
         */
        function get($alias)
        {
            return new AliasDefinitionHelper($alias);
        }

        /**
         * define entry using a factory function
         *
         * @param  string $id
         * @return DefinitionInterface
         */
        function factory(callable $callable, ...$args)
        {
            return new FactoryDefinitionHelper($callable, $args);
        }

        /**
         * define an object entry
         *
         * @param  string $class
         * @return DefineInterface
         */
        function object($class = null)
        {
            return new ObjectDefinitionHelper($class);
        }

        function env($name, $default = null)
        {
            return new EnvDefinitionHelper($name, $default);
        }

        function string($expression)
        {
            return new StringDefinitionHelper($expression);
        }
    }
}
