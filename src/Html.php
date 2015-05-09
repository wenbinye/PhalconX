<?php
namespace PhalconX;

use Phalcon\DI;

class Html
{
    static $clips = array();
    static $clipId;

    static $di;

    public static function beginclip($name)
    {
        ob_start();
        self::$clipId = $name;
    }

    public static function endclip()
    {
        $name = self::$clipId;
        if ( isset(self::$clips[$name]) ) {
            self::$clips[$name] .= ob_get_clean();
        } else {
            self::$clips[$name] = ob_get_clean();
        }
    }

    public static function clip($name)
    {
        return isset(self::$clips[$name]) ? self::$clips[$name] : '';
    }

    /**
     * @param Phalcon\Validation\Message\Group $msgs
     */
    public static function form_errors($msgs, $sep=", ")
    {
        $str = array();
        foreach ( $msgs as $attr => $msg ) {
            $str[] = $msg->getMessage();
        }
        return implode($sep, $str);
    }

    public static function absolute_url($uri=null, $args=null)
    {
        static $BASE_URL;
        if ( !$BASE_URL ) {
            $di = self::getDI();
            $request = $di->getRequest();
            $BASE_URL = $request->getScheme() . '://' . $_SERVER['HTTP_HOST'];
        }
        if ( isset($uri) ) {
            $uri = ltrim($uri, "/");
            return $BASE_URL . self::getDI()->getUrl()->get($uri, $args);
        } else {
            return $BASE_URL . self::getDI()->getUrl()->getBaseUri();
        }
    }

    public static function assertConstant($constant)
    {
        return defined($constant) && constant($constant);
    }

    public static function trim($str, $charlist=null)
    {
        return isset($charlist) ? trim($str, $charlist) : trim($str);
    }

    public static function ltrim($str, $charlist=null)
    {
        return isset($charlist) ? ltrim($str, $charlist) : ltrim($str);
    }

    public static function rtrim($str, $charlist=null)
    {
        return isset($charlist) ? rtrim($str, $charlist) : rtrim($str);
    }

    public static function static_url($path)
    {
        static $staticBaseUri;
        static $assetsPrefix;
        $config = self::getDI()->getConfig();
        if ( !$staticBaseUri ) {
            $staticBaseUri = $config->staticBaseUri;
            if (isset($config->assets)) {
                $assetsPrefix = $config->assets[0] . "/";
            }
        }
        if (isset($assetsPrefix) && substr($path, 0, strlen($assetsPrefix)) == $assetsPrefix) {
            $path = $assetsPrefix . $config->assets[1] . substr($path, strlen($assetsPrefix)-1);
        }
        return $staticBaseUri . $path;
    }

    protected static function getDI()
    {
        if ( !self::$di ) {
            self::$di = DI::getDefault();
        }
        return self::$di;
    }
}
