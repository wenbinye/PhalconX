<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Exception\FilterException;
use PhalconX\Enum\ErrorCode;

class LoginOnly extends AbstractFilter
{
    public $priority = 101;
    
    public function filter()
    {
        if ($this->auth->isGuest()) {
            $this->response->setStatusCode(401);
            throw new FilterException(ErrorCode::LOGIN_REQUIRED);
        }
    }
}
