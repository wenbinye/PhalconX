<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Exception\FilterException;
use PhalconX\Enum\ErrorCode;

class Acl extends AbstractFilter
{
    public $value;
    
    public function filter()
    {
        if ($this->auth->isGuest()) {
            $this->response->setStatusCode(401);
            throw new FilterException(ErrorCode::LOGIN_REQUIRED);
        }
        foreach ($this->getRoles() as $role) {
            if (!$this->roleManager->checkAccess($this->auth->user_id, $role)) {
                $this->response->setStatusCode(403);
                throw new FilterException(ErrorCode::ACCESS_DENIED);
            }
        }
    }

    public function getRoles()
    {
        $roles = $this->value;
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        return $roles;
    }
}
