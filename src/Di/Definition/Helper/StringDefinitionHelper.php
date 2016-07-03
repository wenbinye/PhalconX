<?php
namespace PhalconX\Di\Definition\Helper;

use PhalconX\Di\Definition\StringDefinition;

class StringDefinitionHelper implements DefinitionHelperInterface
{
    private $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition($name)
    {
        return new StringDefinition($name, $this->expression);
    }
}
