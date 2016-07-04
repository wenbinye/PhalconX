<?php
namespace PhalconX\Helper;

class ApcCompat
{
    public static function aliasAll()
    {
        if (function_exists('apc_fetch')) {
            return;
        }
        if (function_exists('apcu_fetch')) {
            foreach (['fetch', 'store', 'inc', 'dec', 'delete', 'exists'] as $name) {
                self::alias($name);
            }
        }
    }

    public static function alias($name)
    {
        $old = 'apc_' . $name;
        $new = 'apcu_' . $name;
        if (!function_exists($old)) {
            eval("namespace {
   function $old () {
      call_user_func_array('$new', func_get_args());
   }
}");
        }
    }
}
