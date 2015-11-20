<?php
namespace PhalconX\Mvc;

/**
 * Simple model implements array access and object property access
 */
abstract class SimpleModel implements \ArrayAccess
{
    /**
     * @var array fields to serialize
     */
    private static $SERIALIZABLE_FIELDS;

    /**
     * Constructor.
     */
    public function __construct(array $data = null)
    {
        if ($data === null) {
            return;
        }
        $this->assign($data);
    }

    /**
     * Updates attributes
     */
    public function assign($attrs)
    {
        foreach ($attrs as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }
    
    public function offsetExists($offset)
    {
         return isset($this->$offset);
    }
  
    public function offsetGet($offset)
    {
         return $this->$offset;
    }
  
    public function offsetSet($offset, $value)
    {
         $this->$offset = $value;
    }
  
    public function offsetUnset($offset)
    {
         unset($this->$offset);
    }

    /**
     * Gets all attributes
     */
    public function toArray()
    {
        return get_object_vars($this);
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
