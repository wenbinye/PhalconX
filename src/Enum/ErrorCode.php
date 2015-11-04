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

    protected static $PROPERTIES = [
        'message' => [
            self::HTTP_METHOD_INVALID       => 'HTTP method is not suported for this request',
            self::MISSING_REQUIRED_ARGUMENT => 'Miss required parameter \':arg\'',
            self::INVALID_ARGUMENT          => 'Parameter :arg\'s value is invalid',
            self::SERVICE_UNAVAILABLE       => 'Service is currently unavailable',
            self::ACCESS_DENIED             => 'Access denied',
            self::CSRF_TOKEN_INVALID        => 'Invalid request, likely attacking',
            self::LOGIN_REQUIRED            => 'The page is displaying for user login only',
            self::NOT_FOUND                 => 'The request url \':url\' is not valid',
            self::USER_NOT_FOUND            => 'The user is not found',
            self::USER_ID_EXISTS            => 'The user already exists',
            self::MODEL_VALIDATATION        => 'The model is not valid',
        ],
    ];
}
