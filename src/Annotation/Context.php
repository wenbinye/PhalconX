<?php
namespace PhalconX\Annotations;

class Context
{
    const TYPE_CLASS = 'class';
    const TYPE_METHOD = 'method';
    const TYPE_PROPERTY = 'property';

    private $class;

    private $declaringClass;

    private $type;

    private $name;

    /**
     * Constructor.
     */
    public function __construct($class, $declaringClass, $type, $name)
    {
        $this->class = $class;
        $this->declaringClass = $declaringClass;
        $this->type = $type;
        $this->name = $name;
    }

    public function onClass()
    {
        return $this->type == self::TYPE_CLASS;
    }

    public function onProperty()
    {
        return $this->type == ContextType::T_PROPERTY;
    }

    public function onMethod()
    {
        return $this->type == ContextType::T_METHOD;
    }
}
