<?php
namespace PhalconX\Annotations\Cli;

use PhalconX\Annotations\Annotation;
use PhalconX\Cli\Router;

class TaskGroup extends Annotation
{
    protected static $DEFAULT_PROPERTY = 'name';

    public $name;

    public $help;

    public $namespace;

    public $class;

    public $module;

    public $tasks;

    public function getId()
    {
        return $this->namespace . ':' . $this->name;
    }

    public function getName()
    {
        return $this->module
            ? $this->module . Router::SEPARATOR . $this->name
            : $this->name;
    }

    public function addTask($task)
    {
        $this->tasks[] = $task;
    }
}
