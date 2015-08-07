<?php
namespace PhalconX\Enums;

use PhalconX\Exception;

class Boolean extends Enum
{
    const TRUE = 1;
    const FALSE = 0;

    /**
     * @param string|int value
     * @return "true", "1", 1, true = TRUE
     *    "false", "0", 0, false = FALSE
     * @throws Exception
     */
    public static function valueOf($value)
    {
        if ($value === false) {
            return new Boolean('FALSE', self::FALSE);
        }
        $name = strtoupper($value);
        if (self::hasName($name)) {
            return parent::valueOf($name);
        }
        $ret = parent::nameOf($name);
        if (isset($ret)) {
            return new Boolean($ret, $value);
        }
        throw new Exception("unknown boolean value '$value'");
    }
}
