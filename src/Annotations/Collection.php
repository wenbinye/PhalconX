<?php
namespace PhalconX\Annotations;

/**
 * (new Collection($annotations))
 * ->classOnly()
 * ->methods()
 * ->methodsOnly()
 * ->properties()
 * ->propertiesOnly()
 * ->isa($annotationClass);
 * ->method($name) // only class annoation and method match $name
 * ->property($name)
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

    public function classOnly()
    {
        return $this->filter(['classOnly' => true]);
    }

    public function methods()
    {
        return $this->filter(['methods' => true]);
    }

    public function methodsOnly()
    {
        return $this->filter(['methodsOnly' => true]);
    }

    public function properties()
    {
        return $this->filter(['properties' => true]);
    }

    public function propertiesOnly()
    {
        return $this->filter(['propertiesOnly' => true]);
    }

    public function isa($clz)
    {
        return $this->filter(['isa' => $clz]);
    }

    public function method($name)
    {
        return $this->filter(['method' => $name]);
    }

    public function property($name)
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
