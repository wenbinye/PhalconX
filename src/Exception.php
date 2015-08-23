<?php
namespace PhalconX;

use Phalcon\Exception as BaseException;

class Exception extends BaseException
{
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
}
