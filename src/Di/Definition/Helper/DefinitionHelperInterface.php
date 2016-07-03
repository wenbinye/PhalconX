<?php
namespace PhalconX\Di\Definition\Helper;

interface DefinitionHelperInterface
{
    /**
     * @return \PhalconX\Di\DefinitionInterface
     */
    public function getDefinition($name);
}
