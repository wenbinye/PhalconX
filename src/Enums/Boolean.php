<?php
namespace PhalconX\Enums;

class Boolean extends Enum
{
    const TRUE = 1;
    const FALSE = 0;

    /**
     * @param string|int value
     * @return "true", "1", 1, true = TRUE
     *    "false", "0", 0, false = FALSE
     *    other value = null
     */
    public static function valueOf($value)
    {
        if (is_string($value)) {
            $name = strtoupper($value);
            $ret = parent::valueOf($name);
            if (isset($ret)) {
                return $ret;
            }
        }
        $ret = parent::name($value);
        if (isset($ret)) {
            return $value;
        }
        return null;
    }
}
