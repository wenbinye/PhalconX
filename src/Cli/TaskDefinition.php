<?php
namespace PhalconX\Cli;

use PhalconX\Mvc\SimpleModel;

class TaskDefinition extends SimpleModel
{
    public $namespace;
    public $task;
    public $help;
    public $options = [];
    public $arguments = [];
}
