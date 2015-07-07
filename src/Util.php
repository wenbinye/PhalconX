<?php
namespace PhalconX;

use Phalcon\Di;

class Util
{
    public static function di()
    {
        return Di::getDefault();
    }

    public static function service($name, $options = null, $required = true)
    {
        if (isset($options[$name])) {
            return $options[$name];
        }
        if (self::di()->has($name)) {
            return self::di()->getShared($name);
        } elseif ($required) {
            throw new \UnexpectedValueException("Service '$name' is not defined yet");
        }
    }
}
