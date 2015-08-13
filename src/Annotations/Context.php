<?php
namespace PhalconX\Annotations;

use PhalconX\Mvc\SimpleModel;

class Context extends SimpleModel
{
    public $class;

    public $declaringClass;

    public $type;

    public $method;

    public $property;

    public function isClass()
    {
        return $this->type == ContextType::T_CLASS;
    }

    public function isProperty()
    {
        return $this->type == ContextType::T_PROPERTY;
    }

    public function isMethod()
    {
        return $this->type == ContextType::T_METHOD;
    }
}
