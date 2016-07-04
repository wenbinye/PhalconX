<?php
namespace PhalconX\Exception;

class CsrfTokenInvalidException extends HttpException
{
    public function __construct($message = null, $previous = null)
    {
        parent::__construct(400, $message, $previous);
    }
}
