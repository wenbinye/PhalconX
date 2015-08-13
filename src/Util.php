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

    public static function fetch($array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
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

    public static function template($template, $context)
    {
        return preg_replace_callback('/{([^{}]+)}/', function ($matches) use ($context) {
            return self::fetch($context, $matches[1], $matches[0]);
        }, $template);
    }

    public static function iterator($array)
    {
        $arr = new \ArrayObject($array);
        return $arr->getIterator();
    }

    public static function walkdir($dir, $callback, $ignoreHide = true)
    {
        $it = new \RecursiveDirectoryIterator($dir);
        foreach (new \RecursiveIteratorIterator($it) as $file => $fileInfo) {
            $name = $fileInfo->getFilename();
            if ($name == '.' || $name == '..') {
                continue;
            }
            if ($ignoreHide && $name[0] == '.') {
                continue;
            }
            call_user_func($callback, $file, $fileInfo);
        }
    }

    public static function parseShellArguments($args)
    {
        preg_match_all('#(?<!\\\\)("|\')(?:[^\\\\]|\\\\.)*?\1|\S+#s', $args, $matches);
        $argv = [];
        foreach ($matches[0] as $arg) {
            if (in_array($arg[0], ['"', '\''])) {
                $len = strlen($arg);
                if ($len < 2 || substr($arg, $len-1, 1) != $arg[0]) {
                    throw new Exception("Parse argument error at '$arg', unmatch quotes");
                }
                $arg = stripcslashes(substr($arg, 1, -1));
            }
            $argv[] = $arg;
        }
        return $argv;
    }

    public static function splitClassName($class)
    {
        $pos = strrpos($class, '\\');
        if ($pos !== false) {
            return [substr($class, 0, $pos), substr($class, $pos+1)];
        } else {
            return [null, $class];
        }
    }
}
