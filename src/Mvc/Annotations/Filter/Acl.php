<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Exception\AccessDeniedException;
use PhalconX\Exception\UnauthorizedException;

class Acl extends AbstractFilter
{
    public $value;
    
    public function filter()
    {
        if ($this->auth->isGuest()) {
            throw new UnauthorizedException();
        }
        foreach ($this->getRoles() as $role) {
            if (!$this->roleManager->checkAccess($this->auth->user_id, $role)) {
                throw new AccessDeniedException();
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
