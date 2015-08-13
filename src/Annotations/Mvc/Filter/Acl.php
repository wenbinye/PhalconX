<?php
namespace PhalconX\Annotations\Mvc\Filter;

use PhalconX\Exception;

class Acl extends AbstractFilter
{
    public $value;
    
    public function filter()
    {
        if ($this->auth->isGuest()) {
            $this->response->setStatusCode(401);
            throw new Exception(
                'The page is displaying for user login only',
                Exception::ERROR_LOGIN_REQUIRED
            );
        }
        foreach ($this->value as $role) {
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
