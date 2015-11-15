<?php
namespace PhalconX\Exception;

use Phalcon\Di;

class HttpException extends Exception
{
    private $statusCode;
    
    public function __construct($statusCode, $message = null, $previous = null)
    {
        $this->statusCode = $statusCode;
        $response = Di::getDefault()->getResponse();
        $response->setStatusCode($statusCode);
        parent::__construct($message ?: $response->getStatusCode(), $statusCode, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
