<?php
namespace PhalconX\Di\Definition\Helper;

use PhalconX\Di\Definition\ValueDefinition;

class ValueDefinitionHelper implements DefinitionHelperInterface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition($name)
    {
        return new ValueDefinition($name, $this->value);
    }
}
