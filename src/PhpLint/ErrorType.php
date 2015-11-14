<?php
namespace PhalconX\PhpLint;

use PhalconX\Enum\Enum;

class ErrorType extends Enum
{
    const USE_CONFLICT = '1001';
    const CLASS_NOT_EXIST = '1002';
    const SYNTAX_ERROR = '1003';

    protected static $PROPERTIES = [
        'description' => [
            self::USE_CONFLICT => 'The import :class name conflicts with previous one',
            self::CLASS_NOT_EXIST => 'The class :class not exist',
            self::SYNTAX_ERROR => 'Syntax error'
        ]
    ];
}
