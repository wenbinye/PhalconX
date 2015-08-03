<?php
namespace PhalconX\Cli\Task;

use PhalconX\Mvc\SimpleModel;
use PhalconX\Cli\Router;

class GroupDefinition extends SimpleModel
{
    public $help;
    public $namespace;
    public $class;
    public $name;
    public $module;
    public $tasks;

    public function getId()
    {
        return $this->namespace . ':' . $name;
    }

    public function getName()
    {
        if (empty($this->name)) {
            throw new Exception("Group name is not defined " . json_encode($this));
        }
        return $this->module ? $this->module . Router::SEPARATOR . $this->name
            : $this->name;
    }

    public function addTask($task)
    {
        $this->tasks[] = $task;
    }
}
