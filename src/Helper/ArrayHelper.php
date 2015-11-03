<?php
namespace PhalconX\Helper;

class ArrayHelper
{
    /**
     * Gets object field value by getter method
     */
    const GETTER = 'getter';

    /**
     * Gets object field value by property
     */
    const OBJ = 'obj';

    /**
     * Gets value from array by key
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function fetch($array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * Collects value from array
     *
     * @param array $array
     * @param string $name
     * @param string $type
     * @return array
     */
    public static function pull($arr, $name, $type = null)
    {
        $ret = [];
        if ($type == self::GETTER) {
            $method = 'get' . $name;
            foreach ($arr as $elem) {
                $ret[] = $elem->$method();
            }
        } elseif ($type == self::OBJ) {
            foreach ($arr as $elem) {
                $ret[] = $elem->$name;
            }
        } else {
            foreach ($arr as $elem) {
                $ret[] = $elem[$name];
            }
        }
        return $ret;
    }

    /**
     * Creates associated array
     *
     * @param array $array
     * @param string $name
     * @param string $type
     * @return array
     */
    public static function assoc($arr, $name, $type = null)
    {
        $ret = [];
        if (empty($arr)) {
            return $ret;
        }
        if ($type == self::GETTER) {
            $method = 'get' . $name;
            foreach ($arr as $elem) {
                $ret[$elem->$method()] = $elem;
            }
        } elseif ($type == self::OBJ) {
            foreach ($arr as $elem) {
                $ret[$elem->$name] = $elem;
            }
        } else {
            foreach ($arr as $elem) {
                $ret[$elem[$name]] = $elem;
            }
        }
        return $ret;
    }

    /**
     * Excludes key in given keys
     *
     * @param array $array
     * @param array $excludedKeys
     * @return array
     */
    public static function exclude($arr, $excludedKeys)
    {
        return array_diff_key($arr, array_flip($excludedKeys));
    }

    /**
     * Create array with given keys
     *
     * @param array $array
     * @param array $includedKeys
     * @return array
     */
    public static function select($arr, $includedKeys)
    {
        $ret = [];
        foreach ($includedKeys as $key) {
            if (array_key_exists($key, $arr)) {
                $ret[$key] = $arr[$key];
            }
        }
        return $ret;
    }

    /**
     * Filter null value
     *
     * @param array $arr
     * @return array
     */
    public static function filter($arr)
    {
        return array_filter($arr, function ($elem) {
            return isset($elem);
        });
    }

    /**
     * create sorter
     *
     * @param string $name
     * @param closure $func
     * @param string $type
     * @return closure
     */
    public static function sorter($name, $func = null, $type = null)
    {
        if (!isset($func)) {
            $func = function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }
                return $a < $b ? -1 : 1;
            };
        }
        
        if ($type == self::GETTER) {
            $method = 'get' . $name;
            return function ($a, $b) use ($method, $func) {
                return call_user_func($func, $a->$method(), $b->$method());
            };
        } elseif ($type == self::OBJ) {
            return function ($a, $b) use ($name, $func) {
                return call_user_func($func, $a->$name, $b->$name);
            };
        } else {
            return function ($a, $b) use ($name, $func) {
                return call_user_func($func, $a[$name], $b[$name]);
            };
        }
    }
}
