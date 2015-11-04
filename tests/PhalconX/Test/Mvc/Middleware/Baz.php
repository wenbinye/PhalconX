<?php
namespace PhalconX\Test\Mvc\Middleware;

use PhalconX\Mvc\Annotations\Filter\AbstractFilter;

class Baz extends AbstractFilter
{
    public $name;
    
    public function filter()
    {
        $this->registry->orders[] = $this->name;
    }
}
