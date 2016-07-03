<?php
namespace PhalconX\Di\Definition;

use Phalcon\DiInterface;
use Phalcon\Di\Exception;

class StringDefinition extends AbstractDefinition
{
    public function resolve($parameters = null, DiInterface $container = null)
    {
        return preg_replace_callback('#\{([^\{\}]+)\}#', function (array $matches) use ($container) {
            try {
                return $container->get($matches[1]);
            } catch (\Exception $e) {
                throw new Exception(sprintf(
                    "Error while parsing string expression for entry '%s': %s",
                    $this->getName(),
                    $e->getMessage()
                ), 0, $e);
            }
        }, $this->definition);
    }
}
