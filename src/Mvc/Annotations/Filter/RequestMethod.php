<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Exception\FilterException;
use PhalconX\Enum\ErrorCode;

class RequestMethod extends AbstractFilter
{
    protected static $DEFAULT_PROPERTY = 'methods';

    public $methods;

    public function filter()
    {
        if (!in_array($this->request->getMethod(), $this->methods)) {
            $this->response->setStatusCode(405);
            throw new FilterException(ErrorCode::HTTP_METHOD_INVALID);
        }
    }
}
