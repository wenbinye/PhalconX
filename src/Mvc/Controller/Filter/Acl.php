<?php
namespace PhalconX\Mvc\Controller\Filter;

use Phalcon\Di\Injectable;
use PhalconX\Util;
use PhalconX\Exception;

class Acl extends Injectable implements FilterInterface
{
    private $roles;
    private $roleManager;
    private $auth;

    public function __construct($roles)
    {
        $this->roles = $roles;
        $this->auth = Util::service('auth');
        $this->roleManager = Util::service('roleManager');
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
        foreach ($this->roles as $role) {
            if (!$this->roleManager->checkAccess($this->auth->user_id, $role)) {
                $this->response->setStatusCode(403);
                throw new Exception(
                    'Access denied',
                    Exception::ERROR_ACCESS_DENIED
                );
            }
        }
    }
}
