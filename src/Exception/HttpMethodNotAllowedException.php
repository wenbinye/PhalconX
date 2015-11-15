<?php
namespace PhalconX\Exception;

class HttpMethodNotAllowedException extends HttpException
{
    public function __construct($message = null, $previous = null)
    {
        parent::__construct(405, $message, $previous);
    }
}
