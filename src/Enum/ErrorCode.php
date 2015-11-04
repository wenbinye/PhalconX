<?php
namespace PhalconX\Enum;

class ErrorCode extends Enum
{
    //  系统级错误
    const HTTP_METHOD_INVALID       = "0001";
    const MISSING_REQUIRED_ARGUMENT = "0002";
    const INVALID_ARGUMENT          = "0003";
    const SERVICE_UNAVAILABLE       = "0004";
    const ACCESS_DENIED             = "0005";
    const CSRF_TOKEN_INVALID        = "0006";

    // 应用级错误
    const LOGIN_REQUIRED     = "0101";
    const NOT_FOUND          = "0102";
    const USER_NOT_FOUND     = "0103";
    const USER_ID_EXISTS     = "0104";
    const MODEL_VALIDATATION = "0105";
}
