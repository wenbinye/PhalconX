<?php
namespace PhalconX\Mvc\Controller\Filter;

use Phalcon\Di\Injectable;
use PhalconX\Util;
use PhalconX\Exception;

class LoginOnly extends Injectable implements FilterInterface
{
    private $auth;
    
    public function __construct($options = null)
    {
        $this->auth = Util::service('auth', $options);
    }

    public function filter($dispatcher)
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
