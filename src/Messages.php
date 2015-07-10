<?php
namespace PhalconX;

use Phalcon\DI;

class Messages
{
    private static $LOADED = false;
    private static $MESSAGES = [];

    private $locale;

    private static function load()
    {
        if (self::$LOADED) {
            return;
        }
        self::$LOADED = true;
        $config = DI::getDefault()->getConfig();
        if (!isset($config->locales)) {
            return;
        }
        $locales = $config->locales->toArray();
        if (empty($locales) || !isset($locales[0])) {
            throw new \UnexpectedValueException("locales configuration requires a default value");
        }
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
            $locale = \Locale::acceptFromHttp($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
        }
        if (empty($locale)) {
            // use first locale in configuration
            $locale = $locales[0];
        } elseif (isset($locales[$locale])) {
            // use locale alias
            $locale = $locales[$locale];
        } elseif (!in_array($locale, $locales)) {
            // use first locale in configuration if not found
            $locale = $locales[0];
        }
        if (isset($config->baseDir) && file_exists($config->baseDir."/messages/$locale.php")) {
            self::$MESSAGES = array_merge(self::$MESSAGES, require($config->baseDir."/messages/$locale.php"));
        }
    }
    
    public static function format($args)
    {
        self::load();
        if (!is_array($args)) {
            $args = func_get_args();
        }
        if (!isset($args[0])) {
            throw new \InvalidArgumentException("Messages::format($id, ...)");
        }
        $msg = isset(self::$MESSAGES[$args[0]]) ? self::$MESSAGES[$args[0]] : $args[0];
        if (count($args)==1) {
            return $msg;
        } elseif (is_array($args[1])) {
            return strtr($msg, $args[1]);
        } else {
            $i = 0;
            return preg_replace_callback("/:\w+/", function () use ($args, &$i) {
                $i++;
                return isset($args[$i]) ? $args[$i] : '';
            }, $msg);
        }
    }
}
