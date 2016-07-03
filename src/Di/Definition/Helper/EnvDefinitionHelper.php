<?php
namespace PhalconX\Di\Definition\Helper;

use PhalconX\Di\Definition\EnvDefinition;

class EnvDefinitionHelper implements DefinitionHelperInterface
{
    private $envName;

    private $default;

    public function __construct($envName, $default = null)
    {
        $this->envName = $envName;
        $this->default = $default;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition($name)
    {
        return (new EnvDefinition($name, $this->envName))
            ->setDefault($this->default);
    }
}
