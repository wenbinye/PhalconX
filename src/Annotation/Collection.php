<?php
namespace PhalconX\Annotation;

/**
 * Annotation collection
 */
class Collection extends \ArrayIterator
{
    private $annotations;

    public function __construct($annotations)
    {
        $this->annotations = $annotations;
        parent::__construct($annotations);
    }
    
    public function filter($conditions)
    {
        $filtered = [];
        foreach ($this as $annotation) {
            if ((!isset($conditions['classOnly']) || $annotation->isClass())
                && (!isset($conditions['methodsOnly']) || $annotation->isMethod())
                && (!isset($conditions['propertiesOnly']) || $annotation->isProperty())
                && (!isset($conditions['methods'])
                    || ($annotation->isClass() || $annotation->isMethod()))
                && (!isset($conditions['properties'])
                    || ($annotation->isClass() || $annotation->isProperty()))
                && (!isset($conditions['isa']) || is_a($annotation, $conditions['isa']))
                && (!isset($conditions['method'])
                    || ($annotation->isMethod()
                        && $annotation->getMethod() == $conditions['method']))
                && (!isset($conditions['property'])
                    || ($annotation->isProperty()
                        && $annotation->getProperty() == $conditions['property']))) {
                $filtered[] = $annotation;
            }
        }
        return new Collection($filtered);
    }

    public function onClass()
    {
        return $this->filter(['on' => ['class']]);
    }

    public function onMethods()
    {
        return $this->filter(['on' => ['method']]);
    }

    public function onClassOrMethods()
    {
        return $this->filter(['on' => ['class', 'method']]);
    }

    public function onProperties()
    {
        return $this->filter(['on' => ['property']]);
    }

    public function onClassOrProperties()
    {
        return $this->filter(['on' => ['class', 'property']]);
    }

    public function is($clz)
    {
        return $this->filter(['is' => $clz]);
    }

    public function onMethod($name)
    {
        return $this->filter(['method' => $name]);
    }

    public function onProperty($name)
    {
        return $this->filter(['property' => $name]);
    }

    public function merge(Collection $other)
    {
        return new self(array_merge($this->annotations, $other->annotations));
    }

    public function toArray()
    {
        return $this->annotations;
    }
}
