<?php
namespace PhalconX\Exception;

class InternalServerErrorException extends HttpException
{
    public function __construct($message = null, $previous = null)
    {
        parent::__construct(500, $message, $previous);
    }
}
