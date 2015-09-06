<?php
namespace PhalconX\Annotations\Mvc\Filter;

use Phalcon\Di\Injectable;

class Json extends AbstractFilter
{
    public $priority = 100;
    
    public function filter()
    {
        $this->response->setContentType('application/json');
    }
}
