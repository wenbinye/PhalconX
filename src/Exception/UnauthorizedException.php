<?php
namespace PhalconX\Exception;

class UnauthorizedException extends HttpException
{
    public function __construct($message = null, $previous = null)
    {
        parent::__construct(401, $message, $previous);
    }
}
