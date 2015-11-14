<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Exception\UnauthorizedException;

class LoginOnly extends AbstractFilter
{
    public $priority = 101;
    
    public function filter()
    {
        if ($this->auth->isGuest()) {
            $this->response->setStatusCode(401);
            throw new UnauthorizedException();
        }
    }
}
