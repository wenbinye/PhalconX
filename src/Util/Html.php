<?php
namespace PhalconX\Util;

use Phalcon\Di;

class Html
{
    private static $clips = array();
    private static $clipId;
    
    public static function beginclip($name)
    {
        ob_start();
        self::$clipId = $name;
    }

    public static function endclip()
    {
        $name = self::$clipId;
        if (isset(self::$clips[$name])) {
            self::$clips[$name] .= ob_get_clean();
        } else {
            self::$clips[$name] = ob_get_clean();
        }
    }

    public static function clip($name)
    {
        return isset(self::$clips[$name]) ? self::$clips[$name] : '';
    }

    public static function absoluteUrl($uri = null, $args = null)
    {
        static $BASE_URL;
        if (!$BASE_URL) {
            $request = Di::getDefault()->getRequest();
            $BASE_URL = $request->getScheme() . '://' . $_SERVER['HTTP_HOST'];
        }
        if (isset($uri)) {
            $uri = ltrim($uri, "/");
            return $BASE_URL . Di::getDefault()->getUrl()->get($uri, $args);
        } else {
            return $BASE_URL . Di::getDefault()->getUrl()->getBaseUri();
        }
    }

    public static function assertConstant($constant)
    {
        return defined($constant) && constant($constant);
    }

    public static function trim($str, $charlist = null)
    {
        return isset($charlist) ? trim($str, $charlist) : trim($str);
    }

    public static function ltrim($str, $charlist = null)
    {
        return isset($charlist) ? ltrim($str, $charlist) : ltrim($str);
    }

    public static function rtrim($str, $charlist = null)
    {
        return isset($charlist) ? rtrim($str, $charlist) : rtrim($str);
    }

    public static function staticUrl($path)
    {
        static $staticBaseUri;
        static $assetsPrefix;
        if (!$staticBaseUri) {
            $config = Di::getDefault()->getConfig();
            $staticBaseUri = $config->application->staticBaseUri;
            if (isset($config->application->assets)) {
                $assetsPrefix = $config->application->assets[0] . "/";
            }
        }
        if (isset($assetsPrefix) && substr($path, 0, strlen($assetsPrefix)) == $assetsPrefix) {
            $config = Di::getDefault()->getConfig();
            $path = $assetsPrefix . $config->application->assets[1] . substr($path, strlen($assetsPrefix)-1);
        }
        return $staticBaseUri . $path;
    }
}
