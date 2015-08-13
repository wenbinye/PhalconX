<?php
namespace PhalconX\Enums;

use PhalconX\Exception;
use PhalconX\Util;

/**
 * enum class
 */
abstract class Enum
{
    /**
     * key = className
     * value = array which key is enum value
     */
    private static $values = array();
    /**
     * key = className
     * value = array which key is enum name
     */
    private static $names = array();

    protected static $PROPERTIES = [];

    protected $name;
    protected $value;

    protected function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function name()
    {
        return $this->name;
    }

    public function value()
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function __get($name)
    {
        if (isset(static::$PROPERTIES[$name][$this->value])) {
            return static::$PROPERTIES[$name][$this->value];
        } elseif (property_exists($this, $name)) {
            return $this->$name;
        }
    }
    
    /**
     * Gets all enum values
     * @return array
     */
    public static function values()
    {
        return array_keys(static::getValues());
    }

    /**
     * Gets all enum names
     * @return array
     */
    public static function names()
    {
        return array_keys(static::getNames());
    }
    
    /**
     * Checks whether the enum value exists
     * @return boolean
     */
    public static function hasValue($value)
    {
        return array_key_exists($value, static::getValues());
    }

    /**
     * Gets the name for the enum value
     * @return string
     */
    public static function nameOf($value)
    {
        return Util::fetch(static::getValues(), $value);
    }

    /**
     * Checks whether the name of enum value exists
     * @return boolean
     */
    public static function hasName($name)
    {
        return array_key_exists($name, static::getNames());
    }

    /**
     * Gets the enum value for the name
     * @return Enum
     */
    public static function valueOf($name)
    {
        $names = static::getNames();
        if (array_key_exists($name, $names)) {
            return new static($name, $names[$name]);
        }
        throw new Exception("No enum constant '$name' in class " . get_called_class());
    }

    /**
     * Returns a value when called statically like so: MyEnum::SOME_VALUE() given SOME_VALUE is a class constant
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return static
     * @throws Exception
     */
    public static function __callStatic($name, $arguments)
    {
        return static::valueOf($name);
    }

    protected static function getValues()
    {
        $class = get_called_class();
        if (!array_key_exists($class, self::$values)) {
            $reflect = new \ReflectionClass($class);
            $constants = $reflect->getConstants();
            self::$names[$class] = $constants;
            self::$values[$class] = array_flip($constants);
        }
        return self::$values[$class];
    }

    protected static function getNames()
    {
        $class = get_called_class();
        if (!isset(self::$names[$class])) {
            self::getValues($class);
        }
        return self::$names[$class];
    }
}
