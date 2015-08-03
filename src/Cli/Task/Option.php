<?php
namespace PhalconX\Cli\Task;

use PhalconX\Mvc\SimpleModel;

class Option extends SimpleModel
{
    public $name;
    
    public $short;

    public $long;

    public $help;

    public $required;

    public $optional;

    public $type;

    public $default;

    public $value;
}
