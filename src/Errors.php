<?php
namespace PhalconX;

use Phalcon\DI;

class Errors
{
    const ERROR_UNKNOWN = "Unknown error";
    
    // HTTP 错误代码
    const HTTP_OK           = 200;
    const HTTP_CREATED      = 201;
    const HTTP_NO_CONTENT   = 204;
    const HTTP_BAD_REQUEST  = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN    = 403;
    const HTTP_NOT_FOUND    = 404;

    //  系统级错误
    const ERROR_HTTP_METHOD_INVALID       = 1;
    const ERROR_MISSING_REQUIRED_ARGUMENT = 2;
    const ERROR_INVALID_ARGUMENT          = 3;
    const ERROR_SERVICE_UNAVAILABLE       = 4;
    const ERROR_ACCESS_DENIED             = 5;
    const ERROR_CSRF_TOKEN_INVALID        = 6;

    // 应用级错误
    const ERROR_LOGIN_REQUIRED     = 1001;
    const ERROR_NOT_FOUND          = 1002;
    const ERROR_USER_NOT_FOUND     = 1003;
    const ERROR_USER_ID_EXISTS     = 1004;
    const ERROR_MODEL_VALIDATATION = 1005;

    private static $HTTP_MESSAGES = array(
        // 1xx: Informational - Request received, continuing process
        100 => 'Continue',
        101 => 'Switching Protocols',

        // 2xx: Success - The action was successfully received, understood and
        // accepted
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // 3xx: Redirection - Further action must be taken in order to complete
        // the request
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        // 4xx: Client Error - The request contains bad syntax or cannot be
        // fulfilled
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // 5xx: Server Error - The server failed to fulfill an apparently
        // valid request
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',
    );

    private static $ERRORS = array(
        self::ERROR_HTTP_METHOD_INVALID       => 'HTTP method is not suported for this request',
        self::ERROR_MISSING_REQUIRED_ARGUMENT => 'Miss required parameter \'{arg}\'',
        self::ERROR_INVALID_ARGUMENT          => 'Parameter {arg}\'s value is invalid',
        self::ERROR_SERVICE_UNAVAILABLE       => 'Service is currently unavailable',
        self::ERROR_ACCESS_DENIED             => 'Access denied',
        self::ERROR_CSRF_TOKEN_INVALID        => 'Invalid request, likely attacking',

        self::ERROR_LOGIN_REQUIRED => 'The page is displaying for user login only',
        self::ERROR_NOT_FOUND      => 'The request url \'{url}\' is not valid',
        self::ERROR_USER_NOT_FOUND => 'The user is not found',
        self::ERROR_USER_ID_EXISTS => 'The user already exists',
        self::ERROR_MODEL_VALIDATATION => 'The model is not valid',
    );

    /**
     * 注册错误代码
     */
    public static function registerErrors(array $errors)
    {
        foreach($errors as $key => $val) {
            self::$ERRORS[$key] = $val;
        }
    }
    
    /**
     * 获取错误代码对应的消息
     *
     * <code>
     *  getMessage(Error::INVALID_ARGUMENT, array('{arg}' => 'url')); // 'Parameter url\'s value is invalid',
     * </code>
     *
     * @param int $errorCode 错误代码
     * @param array $params 消息中内插变量
     * @return string 错误消息
     */
    public static function getMessage($errorCode, $params=array(), $desc=null)
    {
        if ( isset(self::$ERRORS[$errorCode]) ) {
            $msg = empty($params) ? self::$ERRORS[$errorCode] : strtr(self::$ERRORS[$errorCode], $params);
        } else {
            $msg = self::ERROR_UNKNOWN;
        }
        return empty($desc) ? $msg : $msg . ': ' . $desc;
    }

    public static function toArray($errorCode, $params=array(), $desc=null)
    {
        return array(
            'error_code' => $errorCode,
            'error' => self::getMessage($errorCode, $params, $desc)
        );
    }

    public static function toJson($errorCode, $params=array(), $desc=null)
    {
        return json_encode(self::toArray($errorCode, $params, $desc));
    }

    public static function getHttpMessage($code)
    {
        return isset(self::$HTTP_MESSAGES[$code]) ? self::$HTTP_MESSAGES[$code] : '';
    }
    
    public static function getLogger()
    {
        static $logger;
        if ( $logger ) {
            return $logger;
        } else {
            return $logger = DI::getDefault()->getLogger();
        }
    }

    public static function getModelErrors($model)
    {
        $errors = array();
        foreach ( $model->getMessages() as $message ) {
            $errors[] = $message->getMessage();
        }
        return $errors;
    }
    
    private static function getDebugBacktrace($back_levels, $skip=0)
    {
        $traces = debug_backtrace();
        $levels = count($traces);
        for ( $i=0; $i<$levels; $i++ ) {
            if( isset($traces[$i]['file'],$traces[$i]['line']) && $traces[$i]['file'] != __FILE__ ) {
                break;
            }
        }
        $i += $skip;
        $msg = '';
        if ( $back_levels == -1 ) {
            $back_levels = $levels;
        }
        $count = 0;
        for ( ; $i<$levels && $back_levels > 0; $i++ ) {
            $trace = $traces[$i];
            if ( isset($trace['file'],$trace['line']) ) {
                $func = isset($trace['function']) ? (isset($trace['class']) ? $trace['class'].$trace['type'].$trace['function'] : $trace['function']) . '()' : '';
                $msg .= ($count>0 ? "\n" : '') . sprintf("#%d %s(%d) %s", $count, $trace['file'], $trace['line'], $func);
                $count++;
                $back_levels--;
            }
        }
        return $msg;
    }

    /**
     * 是否打印TRACE日志
     */
    public static $DEBUG = false;
    /**
     * 打印调用堆栈数
     */
    public static $TRACE_LEVEL = 0;
    public static function log($msg, $level="info", $trace_begins=0)
    {
        if ( self::$DEBUG && self::$TRACE_LEVEL > 0 ) {
            $msg .= "\n" . self::getDebugBacktrace(self::$TRACE_LEVEL, $trace_begins+1);
        }
        self::getLogger()->$level($msg);
    }

    public static function trace($msg, $trace_begins=0)
    {
        if ( self::$DEBUG ) {
            self::log($msg, "debug", $trace_begins);
        }
    }

    public static function registerErrorHandlers()
    {
        set_exception_handler(array(__CLASS__, 'handleException'));
        set_error_handler(array(__CLASS__, 'handleError'), error_reporting());
    }

    private static function reportError($error)
    {
        if ( isset($error['exception']) ) {
            $msg = "Uncaught {$error['exception']}: " . $error['message'];
        } else {
            $msg = "PHP Fatal error: " . $error['message'];
        }
        if ( !empty($error['file']) ) {
            $msg .= " in {$error['file']}:{$error['line']}";
        }
        if ( !empty($error['trace']) ) {
            $msg .= "\nStack trace:\n" . $error['trace'];
        }
        self::log($msg, "error", 1);
    }

    public static function handleException($exception)
    {
        $error = array(
            'exception' => get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        );
        if ( self::$TRACE_LEVEL > 0 ) {
            $error['trace'] = $exception->getTraceAsString();
        }
        self::reportError($error);
    }

    public static function handleError($code, $message, $file, $line)
    {
        if ( !( $code & error_reporting() ) ) {
            return;
        }
        $error = array(
            'code' => $code,
            'message' => $message,
            'file' => $file,
            'line' => $line
        );
        if ( self::$TRACE_LEVEL > 0 ) {
            $error['trace'] = self::getDebugBacktrace(-1);
        }
        self::reportError($error);
    }
}
