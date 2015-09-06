<?php
namespace PhalconX\Annotations\Mvc\Filter;

use PhalconX\Exception;

class LoginOnly extends AbstractFilter
{
    public $priority = 101;
    
    public function filter()
    {
        if ($this->auth->isGuest()) {
            $this->response->setStatusCode(401);
            throw new Exception(
                'The page is displaying for user login only',
                Exception::ERROR_LOGIN_REQUIRED
            );
        }
    }
}
