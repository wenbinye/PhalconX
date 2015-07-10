<?php
namespace PhalconX\Enums;

/**
 * enum class
 */
class Enum
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

    /**
     * Gets all enum values
     * @return array
     */
    public static function values()
    {
        return array_keys(self::getValues(get_called_class()));
    }

    /**
     * Checks whether the enum value
     * @return boolean
     */
    public static function exists($value)
    {
        $values = self::getValues(get_called_class());
        return isset($values[$value]);
    }

    /**
     * Gets the name for the enum value
     * @return string
     */
    public static function name($value)
    {
        $values = self::getValues(get_called_class());
        return isset($values[$value]) ? $values[$value] : null;
    }

    /**
     * Gets the enum value for the name
     * @return mixed
     */
    public static function valueOf($name)
    {
        $names = self::getNames(get_called_class());
        return isset($names[$name]) ? $names[$name] : null;
    }
    
    protected static function getValues($class)
    {
        if (!isset(self::$values[$class])) {
            $reflect = new \ReflectionClass($class);
            $constants = $reflect->getConstants();
            self::$names[$class] = $constants;
            self::$values[$class] = array_flip($constants);
        }
        return self::$values[$class];
    }

    protected static function getNames($class)
    {
        if (!isset(self::$names[$class])) {
            self::getValues($class);
        }
        return self::$names[$class];
    }
}
