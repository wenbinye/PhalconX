<?php
namespace PhalconX\Test;

/**
 * Enable get/set private property
 */
trait Accessible
{
    public function setValue($object, $field_name, $value)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($field_name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public function getValue($object, $field_name)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($field_name);
        $property->setAccessible(true);
        return $property->getValue();
    }
}
