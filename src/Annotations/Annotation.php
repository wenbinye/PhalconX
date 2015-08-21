<?php
namespace PhalconX\Annotations;

use PhalconX\Mvc\SimpleModel;

abstract class Annotation extends SimpleModel
{
    protected static $DEFAULT_PROPERTY = 'value';
    
    private $context;

    private $annotations;

    public function __construct($arguments)
    {
        if (isset($arguments[0]) && !isset($arguments[static::$DEFAULT_PROPERTY])) {
            $arguments[static::$DEFAULT_PROPERTY] = $arguments[0];
        }
        parent::__construct($arguments);
    }
    
    public function getContext()
    {
        static $dummyContext;
        if (!$dummyContext) {
            $dummyContext = new Context;
        }
        return isset($this->context) ? $this->context : $dummyContext;
    }

    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    public function getAnnotations()
    {
        return $this->annotations;
    }

    public function setAnnotations($annotations)
    {
        $this->annotations = $annotations;
        return $this;
    }

    protected function resolve($annotation)
    {
        return $this->annotations->resolveAnnotation($annotation, $this->getContext());
    }

    protected function resolveImport($name)
    {
        return $this->annotations->resolveImport($name, $this->getDeclaringClass());
    }
    
    public function getClass()
    {
        return $this->getContext()->class;
    }

    public function getDeclaringClass()
    {
        return $this->getContext()->declaringClass;
    }
    
    public function getMethod()
    {
        return $this->getContext()->method;
    }

    public function getProperty()
    {
        return $this->getContext()->property;
    }
    
    public function isClass()
    {
        return $this->getContext()->isClass();
    }

    public function isProperty()
    {
        return $this->getContext()->isProperty();
    }

    public function isMethod()
    {
        return $this->getContext()->isMethod();
    }

    public function getContextType()
    {
        return $this->getContext()->type;
    }

    public function __toString()
    {
        if ($this->context) {
            return sprintf(
                "Annotation at %s %s%s%s",
                $this->context->type,
                $this->context->class,
                $this->isMethod() ? "::" . $this->getMethod() : "",
                $this->isProperty() ? "->" . $this->getProperty() : ""
            );
        } else {
            return "Annotation Object";
        }
    }
}
