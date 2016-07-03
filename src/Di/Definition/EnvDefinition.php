<?php
namespace PhalconX\Di\Definition;

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
        $envName = $this->definition;
        return isset($_ENV[$envName]) ? $_ENV[$envName] : $this->default;
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
