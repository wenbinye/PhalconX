<?php
namespace PhalconX\Test\Helper;

class User
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
}
