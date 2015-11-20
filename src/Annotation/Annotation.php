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
    protected $context;

    /**
     * @var array fields to serialize
     */
    private static $SERIALIZABLE_FIELDS;

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
        $this->assign($arguments);
        $this->context = $context ?: Context::dummy();
    }

    protected function assign($arguments)
    {
        foreach ($arguments as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }
    
    public function getContext()
    {
        return $this->context;
    }

    public function getPropertyName()
    {
        return $this->context->isOnProperty() ? $this->context->getName() : null;
    }

    public function getMethodName()
    {
        return $this->context->isOnMethod() ? $this->context->getName() : null;
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

    private static function getSerialiableFields($class)
    {
        if (!isset(self::$SERIALIZABLE_FIELDS[$class])) {
            $fields = [];
            $refl = new \ReflectionClass($class);
            foreach ($refl->getProperties() as $prop) {
                if ($prop->isStatic() || $prop->isPrivate()) {
                    continue;
                }
                $fields[] = $prop->getName();
            }
            self::$SERIALIZABLE_FIELDS[$class] = $fields;
        }
        return self::$SERIALIZABLE_FIELDS[$class];
    }

    public function __sleep()
    {
        return self::getSerialiableFields(get_class($this));
    }
}
