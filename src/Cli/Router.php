<?php
namespace PhalconX\Cli;

use Phalcon\Text;
use PhalconX\Util;

class Router
{
    const COMMAND = 'Command';
    const OPTION = 'Option';
    const ARGUMENT = 'Argument';
    const SEPARATOR = ':';
    
    private $matched;
    private $scriptName;
    private $task;

    private $arguments;
    private $globalOptions;
    private $tasks;
    private $taskSuffix;
    private $reflection;
    private $annotations;
    private $logger;

    public function __construct($options = null)
    {
        $this->globalOptions = Util::fetch($options, 'globalOptions');
        $this->taskSuffix = Util::fetch($options, 'taskSuffix', 'Task');

        $this->reflection = Util::service('reflection', $options);
        $this->annotations = Util::service('annotations', $options);
        $this->logger = Util::service('logger', $options);
    }
    
    public function scan($dir, $module = null)
    {
        $self = $this;
        Util::walkdir($dir, function ($file) use ($self, $module) {
            $self->addFileResource($file, $module);
        });
    }

    public function addFileResource($file, $module = null)
    {
        $classes = $this->reflection->getClasses($file);
        foreach ($classes as $class) {
            if ($this->taskSuffix && !Text::endsWith($class, $this->taskSuffix)) {
                continue;
            }
            $anno = $this->annotations->get($class);
            if (!$anno) {
                continue;
            }
            $annotations = $anno->getClassAnnotations();
            if (!$annotations) {
                continue;
            }
            foreach ($annotations as $annotation) {
                if ($annotation->getName() == self::COMMAND) {
                    $this->addResource($class, $annotation->getNamedArgument('group'), $module);
                }
            }
        }
    }

    public function addResource($handler, $group = null, $module = null)
    {
        $task = $this->parseTaskName($handler);
        if ($group) {
            $name = $module ? $module . self::SEPARATOR . $group : $group;
            $this->tasks['groups'][$name][$task] = $handler;
        } else {
            $name = $module ? $module . self::SEPARATOR . $task : $task;
            $this->tasks['commands'][$task] = $handler;
        }
    }
    
    public function handle($arguments = null)
    {
        $this->reset();
        if ($arguments == null) {
            global $argv;
            $arguments = $argv;
        } elseif (is_string($arguments)) {
            $arguments = Util::parseShellArguments($arguments);
        }
        $this->scriptName = array_shift($arguments);
        $this->arguments = $arguments;
        $this->parseGlobalArguments();
        $this->parseTask();
        if ($this->matched) {
            $this->parseTaskArguments();
        }
    }

    public function wasMatched()
    {
        return $this->matched;
    }

    public function getGlobalParams()
    {
        return $this->getOptionValues($this->globalOptions);
    }
    
    public function getParams()
    {
        $params = $this->getOptionValues($this->task->options);
        foreach ($this->task->arguments as $arg) {
            $params[$arg->name] = $arg->value;
        }
        return $params;
    }

    public function getArguments()
    {
        return $this->arguments;
    }
    
    public function getNamespaceName()
    {
        return $this->task->namespace;
    }

    public function getTaskName()
    {
        return $this->task->task;
    }

    private function reset()
    {
        $this->scriptName = null;
        $this->matched = false;
        $this->task = null;
    }

    private function parseGlobalArguments()
    {
        if (!$this->globalOptions) {
            return;
        }
        $getopt = new Getopt($this->globalOptions);
        $getopt->parse($this->arguments);
        $this->arguments = $getopt->getOperands();
    }
    
    private function parseTask()
    {
        $arg = array_shift($this->arguments);
        if ($this->hasGroup($arg)) {
            $cmd = array_shift($this->arguments);
            if ($this->hasCommand($cmd, $arg)) {
                $this->setMatched($cmd, $arg);
            }
        } elseif ($this->hasCommand($arg)) {
            $this->setMatched($arg);
        }
    }

    private function setMatched($command, $group = null)
    {
        $this->matched = true;
        if ($group) {
            $task = $this->tasks['groups'][$group][$command];
        } else {
            $task = $this->tasks['commands'][$command];
        }
        if (is_string($task)) {
            $this->task = $this->parseTaskDefinition($task);
        } else {
            $this->task = $task;
        }
    }

    private function parseTaskArguments()
    {
        $getopt = new Getopt($this->task->options);
        $getopt->parse($this->arguments);
        $this->arguments = $getopt->getOperands();
        if ($this->task->arguments) {
            foreach ($this->task->arguments as $argument) {
                if ($argument->type == 'array') {
                    $argument->value = $this->arguments;
                    break;
                } else {
                    $argument->value = array_shift($this->arguments);
                }
            }
        }
    }

    private function hasGroup($group)
    {
        if (isset($this->tasks['groups'][$group])) {
            return true;
        }
        $matched = $this->suffixMatch(array_keys($this->tasks['groups']), $group);
        if ($matched) {
            $this->tasks['groups'][$group] = $this->tasks['groups'][$matched];
            return true;
        }
        return false;
    }

    private function hasCommand($command, $group = null)
    {
        if (isset($group)) {
            return isset($this->tasks['groups'][$group][$command]);
        } elseif (isset($this->tasks['commands'][$command])) {
            return true;
        } else {
            $matched = $this->suffixMatch(array_keys($this->tasks['commands']), $command);
            if ($matched) {
                $this->tasks['commands'][$command] = $this->tasks['commands'][$matched];
                return true;
            }
            return false;
        }
    }

    private function suffixMatch($values, $name)
    {
        $matched = null;
        $times = 0;
        $suffix = self::SEPARATOR . $name;
        foreach ($values as $name) {
            if (Text::endsWith($name, $suffix)) {
                $matched = $name;
                $times++;
            }
        }
        if ($times == 1) {
            return $matched;
        }
    }

    private function getOptionValues($options)
    {
        $params = [];
        foreach ($options as $option) {
            $params[$option->name] = $option->value;
        }
        return $params;
    }

    private function parseTaskName($handler)
    {
        $parts = explode('\\', $handler);
        $classname = $parts[count($parts)-1];
        if ($this->taskSuffix && !Text::endsWith($classname, $this->taskSuffix)) {
            throw new Exception("Task handler '$handler' not match suffix '{$this->taskSuffix}'");
        }
        $task = Text::uncamelize(substr($classname, 0, -strlen($this->taskSuffix)));
        return str_replace('_', '-', $task);
    }

    private function parseTaskDefinition($handler)
    {
        $def = new TaskDefinition;
        $def->task = $this->parseTaskName($handler);
        $pos = strrpos($handler, '\\');
        if ($pos !== false) {
            $def->namespace = substr($handler, 0, $pos);
        }
        $anno = $this->annotations->get($handler);
        $class_anno = $anno->getClassAnnotations();
        if ($class_anno) {
            foreach ($class_anno as $annotation) {
                if ($annotation->getName() == self::COMMAND) {
                    $def->help = $annotation->getNamedArgument('help');
                }
            }
        }
        $prop_anno = $anno->getPropertiesAnnotations();
        if ($prop_anno) {
            foreach ($prop_anno as $prop => $annotations) {
                foreach ($annotations as $annotation) {
                    switch ($annotation->getName()) {
                        case self::OPTION:
                            $def->options[] = $this->createOption($prop, $annotation->getArguments());
                            break;
                        case self::ARGUMENT:
                            $def->arguments[] = $this->createArgument($prop, $annotation->getArguments());
                            break;
                    }
                }
            }
        }
        return $def;
    }

    private function createOption($prop, $args)
    {
        if (!isset($args['name'])) {
            $args['name'] = $prop;
        }
        if ($args['type'] == 'boolean') {
            $args['optional'] = true;
        }
        $option = new Option($args);
        foreach ([0, 1] as $i) {
            if (!empty($args[$i]) && $args[$i][0] == '-' && strlen($args[$i]) >= 2) {
                if (!$option->long && Text::startsWith($args[$i], '--')) {
                    $option->long = substr($args[$i], 2);
                } elseif (!$option->short && strlen($args[$i]) == 2 && ctype_alpha($args[$i][1])) {
                    $option->short = substr($args[$i], 1);
                }
            }
        }
        return $option;
    }

    private function createArgument($prop, $args)
    {
        if (!isset($args['name'])) {
            $args['name'] = $prop;
        }
        return new Argument($args);
    }
}
