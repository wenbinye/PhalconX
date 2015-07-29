<?php
namespace PhalconX\Cli;

use PhalconX\Mvc\SimpleModel;

class Argument extends SimpleModel
{
    public $name;

    public $type;

    public $help;

    public $required;

    public $value;
}
