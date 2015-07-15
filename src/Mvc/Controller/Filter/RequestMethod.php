<?php
namespace PhalconX\Mvc\Controller\Filter;

use Phalcon\Di\Injectable;
use PhalconX\Exception;

class RequestMethod extends Injectable implements FilterInterface
{
    private $methods = [];
    
    public function __construct($methods)
    {
        $this->methods = $methods;
    }

    public function filter($dispatcher)
    {
        if (!in_array($this->request->getMethod(), $this->methods)) {
            $this->response->setStatusCode(405);
            throw new Exception(
                'HTTP method is not suported for this request',
                Exception::ERROR_HTTP_METHOD_INVALID
            );
        }
    }
}
