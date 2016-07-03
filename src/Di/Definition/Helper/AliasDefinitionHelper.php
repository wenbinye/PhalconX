<?php
namespace PhalconX\Di\Definition\Helper;

use PhalconX\Di\Definition\AliasDefinition;

class AliasDefinitionHelper implements DefinitionHelperInterface
{
    private $alias;

    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition($name)
    {
        return new AliasDefinition($name, $this->alias);
    }
}
