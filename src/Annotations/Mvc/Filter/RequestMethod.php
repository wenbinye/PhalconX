<?php
namespace PhalconX\Annotations\Mvc\Filter;

use PhalconX\Exception;

class RequestMethod extends AbstractFilter
{
    protected static $DEFAULT_PROPERTY = 'methods';

    public $methods;

    public function filter()
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
