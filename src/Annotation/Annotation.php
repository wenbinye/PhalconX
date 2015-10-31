<?php
namespace PhalconX\Annotation;

/**
 * Annotation
 */
abstract class Annotation
{
    /**
     * default property name
     */
    protected static $DEFAULT_PROPERTY = 'value';

    /**
     * annotation context
     */
    private $context;

    /**
     * Constructor.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments, Context $context = null)
    {
        if (isset($arguments[0]) && !isset($arguments[static::$DEFAULT_PROPERTY])) {
            $arguments[static::$DEFAULT_PROPERTY] = $arguments[0];
        }
        foreach ($arguments as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
        $this->context = $context ?: Context::dummy();
    }
    
    public function getContext()
    {
        return $this->context;
    }
    
    public function getClass()
    {
        return $this->getContext()->getClass();
    }

    public function getDeclaringClass()
    {
        return $this->getContext()->getDeclaringClass();
    }
    
    public function isOnClass()
    {
        return $this->getContext()->isOnClass();
    }

    public function isOnProperty()
    {
        return $this->getContext()->isOnProperty();
    }

    public function isOnMethod()
    {
        return $this->getContext()->isOnMethod();
    }

    public function __toString()
    {
        if ($this->context->getClass()) {
            return "Annotation on " . $this->context;
        } else {
            return "Annotation Object";
        }
    }
}
