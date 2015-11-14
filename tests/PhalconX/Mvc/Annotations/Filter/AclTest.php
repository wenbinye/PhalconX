<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Test\TestCase;
use PhalconX\Mvc\SimpleAuth;
use PhalconX\Exception\UnauthorizedException;
use PhalconX\Enum\ErrorCode;

/**
 * TestCase for Acl
 */
class AclTest extends TestCase
{
    private $filter;

    private $auth;

    /**
     * @before
     */
    public function setupFilter()
    {
        $this->filter = new Acl([]);
        $this->getDi()['auth'] = $this->auth = new SimpleAuth;
    }

    public function testFilter()
    {
        $this->setExpectedException(UnauthorizedException::class);
        $this->filter->filter();
    }

    public function testRole()
    {
        $this->filter->value = 'admin';
    }
}
