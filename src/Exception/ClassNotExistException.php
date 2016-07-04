<?php
namespace PhalconX\Exception;

class ClassNotExistException extends Exception
{
    private $className;
    
    public function __construct($name)
    {
        $this->className = $name;
        parent::__construct("Interface or class '{$name}' does not exist");
    }
    
    public function getClassName()
    {
        return $this->className;
    }
}
