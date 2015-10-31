<?php
namespace PhalconX\Helper;

/**
 * Functions to help parse class name or reflection
 */
class ClassHelper
{
    /**
     * Gets class name without namespace
     *
     * @param string $class
     * @return class simple name
     */
    public static function getShortName($class)
    {
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            return $class;
        } else {
            return substr($class, $pos+1);
        }
    }

    /**
     * Gets namespace name
     *
     * @param string $class
     * @return namespace name
     */
    public static function getNamespaceName($class)
    {
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            return "";
        } else {
            return substr($class, 0, $pos+1);
        }
    }

    /**
     * Gets all imported classes
     *
     * @param string $class
     * @return array import class
     */
    public static function getImports($class)
    {
        $reflect = new \ReflectionClass($class);
        return self::getImportsFromFile($reflect->getFileName());
    }

    /**
     * Gets all imported classes
     *
     * @param string $class
     * @return array import class
     */
    public static function getImportsFromFile($file)
    {
        return (new PhpImportParser($file))->getImports();
    }
}
