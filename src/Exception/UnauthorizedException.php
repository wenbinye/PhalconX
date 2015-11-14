<?php
namespace PhalconX\Exception;

class UnauthorizedException extends FilterException
{
    public function __construct($message = null, $previous = null)
    {
        parent::__construct(401, $message, $previous);
    }
}
