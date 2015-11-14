<?php
namespace PhalconX\Test\Mvc\Middleware;

use PhalconX\Mvc\Annotations\Filter\AbstractFilter;
use PhalconX\Exception\HttpMethodNotAllowedException;

class Foo extends AbstractFilter
{
    public $value;
    
    public function filter()
    {
        $ret = $this->registry->filterExpect;
        if ($ret == 'true') {
            return $this->registry->filterResult = true;
        } elseif ($ret == 'false') {
            return $this->registry->filterResult = false;
        } else {
            throw new HttpMethodNotAllowedException;
        }
    }
}
