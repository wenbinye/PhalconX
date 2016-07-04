<?php
namespace PhalconX\Exception;

class NotFoundException extends HttpException
{
    public function __construct($message = null, $previous = null)
    {
        parent::__construct(404, $message, $previous);
    }
}
