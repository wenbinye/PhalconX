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
        $di = Di::getDefault();
        if ($di->has($name)) {
            return $di->getShared($name);
        } elseif ($required) {
            throw new \UnexpectedValueException("Service '$name' is not defined yet");
        }
    }

    public static function now($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }
}
