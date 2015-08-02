<?php
namespace PhalconX\Cli\Task;

use PhalconX\Mvc\SimpleModel;

class Argument extends SimpleModel
{
    public $name;

    public $type;

    public $help;

    public $required;

    public $value;
}
