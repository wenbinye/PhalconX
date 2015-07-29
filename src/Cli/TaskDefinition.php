<?php
namespace PhalconX\Cli;

use PhalconX\Mvc\SimpleModel;

class TaskDefinition extends SimpleModel
{
    public $namespace;
    public $task;
    public $class;
    public $help;
    public $options = [];
    public $arguments = [];

    public function getId()
    {
        return $this->namespace . '\\' . $this->task;
    }
}
