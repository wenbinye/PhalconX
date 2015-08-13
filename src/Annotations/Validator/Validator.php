<?php
namespace PhalconX\Annotations\Validator;

use Phalcon\Validation\Validator\PresenceOf;
use PhalconX\Annotations\Annotation;

abstract class Validator extends Annotation
{
    public $name;

    public $default;

    public $required;

    private $annotations;

    public function process()
    {
        $validators = [];
        if ($this->required) {
            $validators[] = new PresenceOf();
        }
        return array_merge($validators, $this->getValidators());
    }

    abstract protected function getValidators();

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
}
