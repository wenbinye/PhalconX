<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Exception\HttpMethodNotAllowedException;

class RequestMethod extends AbstractFilter
{
    protected static $DEFAULT_PROPERTY = 'methods';

    public $methods;

    public function filter()
    {
        if (!in_array($this->request->getMethod(), $this->methods)) {
            throw new HttpMethodNotAllowedException();
        }
    }
}
