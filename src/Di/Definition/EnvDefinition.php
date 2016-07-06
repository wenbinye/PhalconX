<?php
namespace PhalconX\Di\Definition;

use PhalconX\Di\Definition\Resolver\EnvDefinitionResolver;
use Phalcon\DiInterface;

class EnvDefinition extends AbstractDefinition
{
    private $default;

    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    public function resolve($parameters = null, DiInterface $container = null)
    {
        $value = EnvDefinitionResolver::findEnvironmentVariable($this->definition);
        return $value === false ? $this->default : $value;
    }

    public static function __set_state(array $attributes)
    {
        $self = parent::__set_state($attributes);
        if (isset($attributes['default'])) {
            $self->setDefault($attributes['default']);
        }
        return $self;
    }
}
