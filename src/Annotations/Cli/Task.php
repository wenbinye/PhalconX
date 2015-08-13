<?php
namespace PhalconX\Annotations\Cli;

use PhalconX\Annotations\Annotation;
use PhalconX\Cli\Router;
use Phalcon\Text;

class Task extends Annotation
{
    protected static $DEFAULT_PROPERTY = 'name';
    
    public $name;
    
    public $namespace;
    
    public $class;
    
    public $method;
    
    public $group;
    
    public $module;
    
    public $help;
    
    public $options = [];
    
    public $arguments = [];
    
    public function getId()
    {
        return $this->namespace . '\\' . $this->class;
    }

    /**
     * 返回
     */
    public function getName()
    {
        if ($this->module) {
            return $this->module . Router::SEPARATOR . $this->getSimpleName();
        } else {
            return $this->getSimpleName();
        }
    }

    public function getSimpleName()
    {
        if (empty($this->name)) {
            throw new Exception("Task name is not defined " . json_encode($this));
        }
        return $this->group ? $this->group . ' ' . $this->name : $this->name;
    }
    
    public function getGroupName()
    {
        return $this->module ? $this->module . Router::SEPARATOR . $this->group
            : $this->group;
    }
}
