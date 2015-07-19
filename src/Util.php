<?php
namespace PhalconX;

use Phalcon\Di;
use Phalcon\Text;

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

    public static function startsWith($str, $start, $stop = null)
    {
        if (isset($stop)) {
            return $str == $start || Text::startsWith($str, $start . $stop);
        } else {
            return Text::startsWith($str, $start);
        }
    }

    public static function uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
