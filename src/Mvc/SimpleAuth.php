<?php
namespace PhalconX\Mvc;

/**
 * auth without storage and session
 */
class SimpleAuth implements AuthInterface
{
    protected $sessionData = false;
    
    public function initialize()
    {
    }
    
    public function __get($name)
    {
        if (isset($this->sessionData[$name])) {
            return $this->sessionData[$name];
        }
    }

    public function __set($name, $value)
    {
        if (isset($this->sessionData[$name])) {
            $this->sessionData[$name] = $value;
        }
    }

    public function login($identity)
    {
        $this->sessionData = array();
        foreach ($identity as $name => $val) {
            $this->sessionData[$name] = $val;
        }
    }
    
    public function logout($destroySession = true)
    {
        $this->sessionData = false;
    }
    
    public function isGuest()
    {
        return false === $this->sessionData;
    }
    
    public function isNeedLogin()
    {
        return $this->isGuest();
    }
}
