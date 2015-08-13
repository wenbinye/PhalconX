<?php
namespace PhalconX\Annotations\Mvc\Filter;

use Phalcon\Di\Injectable;

class Json extends AbstractFilter
{
    public function filter()
    {
        $this->response->setContentType('application/json');
    }
}
