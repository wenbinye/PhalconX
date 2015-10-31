<?php
namespace PhalconX\Annotation;

class FilterIterator extends \FilterIterator
{
    private $filters;
    
    public function accept()
    {
        $annotation = parent::current();
        if (isset($this->filters['is']) && !is_a($annotation, $this->filters['is'])) {
            return false;
        }
        $context = $annotation->getContext();
        if (isset($this->filters['method']) && (!$context->isOnMethod() || $context->getName() != $this->filters['method'])) {
            return false;
        }
        if (isset($this->filters['property']) && (!$context->isOnProperty() || $context->getName() != $this->filters['property'])) {
            return false;
        }
        if (isset($this->filters['on']) && !in_array($context->getType(), $this->filters['on'])) {
            return false;
        }
        return true;
    }

    public function onClass()
    {
        $this->filters['on'] = [Context::TYPE_CLASS];
        return $this;
    }

    public function onMethods()
    {
        $this->filters['on'] = [Context::TYPE_METHOD];
        return $this;
    }

    public function onClassOrMethods()
    {
        $this->filters['on'] = [Context::TYPE_CLASS, Context::TYPE_METHOD];
        return $this;
    }

    public function onProperties()
    {
        $this->filters['on'] = [Context::TYPE_PROPERTY];
        return $this;
    }

    public function onClassOrProperties()
    {
        $this->filters['on'] = [Context::TYPE_CLASS, Context::TYPE_PROPERTY];
        return $this;
    }

    public function is($annotationClass)
    {
        $this->filters['is'] = $annotationClass;
        return $this;
    }

    public function onMethod($methodName)
    {
        $this->filters['method'] = $methodName;
        return $this;
    }

    public function onProperty($propertyName)
    {
        $this->filters['property'] = $propertyName;
        return $this;
    }
}
