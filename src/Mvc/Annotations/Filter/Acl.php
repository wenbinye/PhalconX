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
        if (!$this->roleManager->checkAccess($this->auth->user_id, $this->value)) {
            throw new AccessDeniedException();
        }
    }
}
