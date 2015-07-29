<?php
namespace PhalconX\Cli;

use PhalconX\Mvc\SimpleModel;

class TaskGroupDefinition extends SimpleModel
{
    public $help;
    public $name;
    public $tasks;

    public function getId()
    {
        return 'group:' . $this->name;
    }
}
