<?php
namespace PhalconX\Exception;

use Phalcon\Di;

class FilterException extends Exception
{
    public function __construct($statusCode, $message = null, $previous = null)
    {
        $response = Di::getDefault()->getResponse();
        $response->setStatusCode($statusCode);
        parent::__construct($message ?: $response->getStatusCode(), $statusCode, $previous);
    }
}
