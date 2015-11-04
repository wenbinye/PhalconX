<?php
namespace PhalconX\Mvc\Annotations\Filter;

class Json extends AbstractFilter
{
    public $priority = 100;
    
    public function filter()
    {
        $this->response->setContentType('application/json');
    }
}
