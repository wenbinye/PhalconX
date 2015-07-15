<?php
namespace PhalconX\Mvc\Controller\Filter;

use Phalcon\Di\Injectable;

class Json extends Injectable implements FilterInterface
{
    public function filter($dispatcher)
    {
        $this->response->setContentType('application/json');
    }
}
