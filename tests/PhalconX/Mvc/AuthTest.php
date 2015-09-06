<?php
namespace PhalconX\Mvc;

use PhalconX\Test\TestCase;

/**
 * TestCase for Auth
 */
class AuthTest extends TestCase
{
    private $session;
    private $auth;
    public $sessionData;
    
    public function setUp()
    {
        $this->sessionData = [];
        $self = $this;
        $session = $this->getMock('Phalcon\Session\Adapter\Files');
        $session->expects($this->any())
            ->method('set')
            ->will($this->returnCallback(function($key, $val) use ($self) {
                        $self->sessionData[$key] = $val;
                    }));
        // session_start();
        $this->replaceService('session', $session);
        $this->session = $session;
        $this->auth = new Auth;
    }
    
    public function testEmpty()
    {
        $this->auth->login(['user_id' => 1]);
        print_r($this->sessionData);
    }
}
