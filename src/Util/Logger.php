<?php
namespace PhalconX\Util;

use Phalcon\Di;

class Logger
{
    /**
     * 是否打印TRACE日志
     */
    public static $DEBUG = false;
    /**
     * 打印调用堆栈数
     */
    public static $TRACE_LEVEL = 0;

    private static $LOGGER;

    public static function log($msg, $level = "info", $trace_begins = 0)
    {
        if (self::$DEBUG && self::$TRACE_LEVEL > 0) {
            $msg .= "\n" . self::getDebugBacktrace(self::$TRACE_LEVEL, $trace_begins+1);
        }
        self::getLogger()->$level($msg);
    }

    public static function trace($msg, $trace_begins = 0)
    {
        if (self::$DEBUG) {
            self::log($msg, "debug", $trace_begins);
        }
    }
    
    public static function getLogger()
    {
        if (!self::$LOGGER) {
            self::$LOGGER = Di::getDefault()->getLogger();
        }
        return self::$LOGGER;
    }
    
    private static function getDebugBacktrace($back_levels, $skip = 0)
    {
        $traces = debug_backtrace();
        $levels = count($traces);
        for ($i=0; $i<$levels; $i++) {
            if (isset($traces[$i]['file'],$traces[$i]['line']) && $traces[$i]['file'] != __FILE__) {
                break;
            }
        }
        $i += $skip;
        $msg = '';
        if ($back_levels == -1) {
            $back_levels = $levels;
        }
        $count = 0;
        for (; $i<$levels && $back_levels > 0; $i++) {
            $trace = $traces[$i];
            if (isset($trace['file'],$trace['line'])) {
                if (isset($trace['function'])) {
                    if (isset($trace['class'])) {
                        $func = $trace['class'].$trace['type'].$trace['function'] . '()';
                    } else {
                        $func = $trace['function'] . '()';
                    }
                } else {
                    $func = '';
                }
                $msg .= ($count>0 ? "\n" : '');
                $msg .= sprintf("#%d %s(%d) %s", $count, $trace['file'], $trace['line'], $func);
                $count++;
                $back_levels--;
            }
        }
        return $msg;
    }
}
