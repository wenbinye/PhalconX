<?php
namespace PhalconX\Exception;

use PhalconX\Enum\ErrorCode;

class FilterException extends Exception
{
    public function __construct($errorCode, $previous = null)
    {
        if ($errorCode) {
            parent::__construct(ErrorCode::fromValue($errorCode)->message, $errorCode, $previous);
        } else {
            parent::__construct('', 0, $previous);
        }
    }
}
