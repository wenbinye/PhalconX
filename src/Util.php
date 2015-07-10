<?php
namespace PhalconX;

use Phalcon\Di;

class Util
{
    public static function service($name, $options = null, $required = true)
    {
        if (isset($options[$name])) {
            return $options[$name];
        }
        if (Di::getDefault()->has($name)) {
            return Di::getDefault()->getShared($name);
        } elseif ($required) {
            throw new \UnexpectedValueException("Service '$name' is not defined yet");
        }
    }
}
