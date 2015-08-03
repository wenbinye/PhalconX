<?php
namespace PhalconX\Cli;

use Phalcon\Text;
use PhalconX\Exception;
use PhalconX\Util;
use PhalconX\Cli\Task\Definition;
use PhalconX\Cli\Task\GroupDefinition;
use PhalconX\Cli\Task\Getopt;
use PhalconX\Cli\Task\Option;
use PhalconX\Cli\Task\Argument;

class Router
{
    const TASK_GROUP = 'TaskGroup';
    const TASK = 'Task';
    const OPTION = 'Option';
    const ARGUMENT = 'Argument';
    const SEPARATOR = ':';

    /**
     * @var boolean
     */
    private $matched;
    /**
     * @var string
     */
    private $scriptName;
    /**
     * @var Definition
     */
    private $task;
    /**
     * @var Definition[]
     */
    private $tasks;
    /**
     * @var GroupDefinition[]
     */
    private $groups;
    /**
     * @var string
     */
    private $defaultTask;
    /**
     * @var string[]
     */
    private $arguments;
    /**
     * @var Option[]
     */
    private $globalOptions;
    /**
     * @var string
     */
    private $taskSuffix;
    /**
     * @var string
     */
    private $actionSuffix;
    /**
     * @var boolean
     */
    private $processed;

    private $tasksMap;
    private $groupsMap;
    private $reflection;
    private $annotations;
    private $logger;

    public function __construct($options = null)
    {
        $this->globalOptions = Util::fetch($options, 'globalOptions');
        $this->taskSuffix = Util::fetch($options, 'taskSuffix', 'Task');
        $this->actionSuffix = Util::fetch($options, 'actionSuffix', 'Action');
        $this->defaultTask = Util::fetch($options, 'defaultTask', 'help');

        $this->reflection = Util::service('reflection', $options);
        $this->annotations = Util::service('annotations', $options);
        $this->logger = Util::service('logger', $options);
    }

    /**
     * @param string $dir
     * @param string $module
     */
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
            if ($this->getClassName($class) == self::TASK_GROUP) {
                $this->parseTaskGroupDefinition($class, $module);
            } elseif (!$this->taskSuffix || Text::endsWith($class, $this->taskSuffix)) {
                $this->parseTaskDefinition($class, $module);
            }
        }
        $this->proccesed = false;
    }
    
    public function handle($arguments = null)
    {
        $this->reset();
        $this->processTasks();
        if ($arguments == null) {
            global $argv;
            $arguments = $argv;
        } elseif (is_string($arguments)) {
            $arguments = Util::parseShellArguments($arguments);
        }
        $this->scriptName = array_shift($arguments);
        $this->arguments = $arguments;
        $this->parseGlobalArguments();
        $this->matchTask();
        if ($this->wasMatched()) {
            $this->parseTaskArguments();
        }
    }

    public function wasMatched()
    {
        return $this->matched;
    }

    public function getScriptName()
    {
        return $this->scriptName;
    }

    public function getGlobalOptions()
    {
        return $this->globalOptions;
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
        return $this->task->class;
    }

    public function getActionName()
    {
        return $this->task->method ? $this->task->method : 'execute';
    }

    public function getDefinition($name)
    {
        $this->processTasks();
        if ($this->hasTask($name)) {
            $task = $this->tasksMap[$name];
            $this->parseOptions($task);
            return $task;
        } elseif ($this->hasGroup($name)) {
            return $this->groupsMap[$name];
        }
    }
    
    public function getDefinitions()
    {
        $this->processTasks();
        return $this->tasksMap;
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

    private function parseTaskArguments()
    {
        $getopt = new Getopt($this->task->options);
        $getopt->parse($this->arguments);
        $arguments = $getopt->getOperands();
        if ($this->task->arguments) {
            foreach ($this->task->arguments as $argument) {
                if ($argument->type == 'array') {
                    $argument->value = $arguments;
                    break;
                } else {
                    $argument->value = array_shift($arguments);
                }
            }
        }
    }

    private function matchTask()
    {
        if (empty($this->arguments)) {
            if ($this->hasTask($this->defaultTask)) {
                $this->setMatched($this->defaultTask);
            }
        } else {
            $arg = array_shift($this->arguments);
            if ($this->hasGroup($arg)) {
                $task = $arg . ' ' . array_shift($this->arguments);
                if ($this->hasTask($task)) {
                    $this->setMatched($task);
                }
            } elseif ($this->hasTask($arg)) {
                $this->setMatched($arg);
            }
        }
    }

    private function setMatched($task)
    {
        $this->matched = true;
        $this->task = $this->tasksMap[$task];
        $this->parseOptions($this->task);
    }

    private function parseTaskGroupDefinition($groupClass, $module)
    {
        $anno = $this->annotations->get($groupClass);
        $classAnnotations = $anno->getClassAnnotations();
        if (!$classAnnotations) {
            return;
        }
        foreach ($classAnnotations as $annotation) {
            if ($annotation->getName() != self::TASK_GROUP) {
                continue;
            }
            $def = new GroupDefinition($annotation->getArguments());
            $name = $annotation->getArgument(0);
            if ($name) {
                $def->name = $name;
            }
            if (!$def->name) {
                throw new Exception("Group name is required which defined in '$groupClass'");
            }
            $def->module = $module;
            $def->namespace = $this->getClassNamespace($groupClass);
            $def->class = $this->getClassName($groupClass);
            $this->groups[] = $def;
        }
    }

    private function parseTaskDefinition($class, $module)
    {
        $anno = $this->annotations->get($class);
        if (!$anno) {
            continue;
        }
        $classAnnotations = $anno->getClassAnnotations();
        if ($classAnnotations) {
            foreach ($classAnnotations as $annotation) {
                if ($annotation->getName() == self::TASK) {
                    $this->addResource($class, null, $module, $annotation->getArguments());
                    return;
                } elseif ($annotation->getName() == self::TASK_GROUP) {
                    $this->parseTaskGroupDefinition($class, $module);
                }
            }
        }
        $methodAnnotations = $anno->getMethodsAnnotations();
        if ($methodAnnotations) {
            foreach ($methodAnnotations as $method => $annotations) {
                if ($this->actionSuffix && !Text::endsWith($method, $this->actionSuffix)) {
                    continue;
                }
                foreach ($annotations as $annotation) {
                    if ($annotation->getName() == self::TASK) {
                        $this->addResource($class, $method, $module, $annotation->getArguments());
                        break;
                    }
                }
            }
        }
    }
    
    private function addResource($class, $method, $module, $options)
    {
        $def = new Definition($options);
        $def->namespace = $this->getClassNamespace($class);
        $def->class = $this->getClassName($class);
        $def->method = $method;
        $def->module = $module;
        if (!isset($def->name)) {
            if ($method) {
                if ($this->actionSuffix && !Text::endsWith($method, $this->actionSuffix)) {
                    throw new Exception("Task handler '$class::$method' not match suffix '{$this->actionSuffix}'");
                }
                $def->name = Text::uncamelize(substr($method, 0, -strlen($this->actionSuffix)));
            } elseif ($class) {
                $parts = explode('\\', $class);
                $classname = $parts[count($parts)-1];
                if ($this->taskSuffix && !Text::endsWith($classname, $this->taskSuffix)) {
                    throw new Exception("Task handler '$handler' not match suffix '{$this->taskSuffix}'");
                }
                $def->name = Text::uncamelize(substr($classname, 0, -strlen($this->taskSuffix)));
            }
        }
        $def->name = str_replace('_', '-', $def->name);
        $this->tasks[] = $def;
    }

    private function processTasks()
    {
        if ($this->processed) {
            return;
        }
        if (!$this->tasks) {
            return;
        }
        $groups = [];
        $groupNamespaces = [];
        if ($this->groups) {
            foreach ($this->groups as $group) {
                $name = $group->getName();
                if (isset($groups[$name])) {
                    throw new Exception(sprintf(
                        "Group '{$name}' defined in '%s' conflict with '%s'",
                        json_encode($group),
                        json_encode($groups[$name])
                    ));
                }
                $groups[$name] = $group;
                if ($group->namespace) {
                    if ($group->class == self::TASK_GROUP) {
                        $groupNamespaces[$group->namespace] = $group;
                    } else {
                        $groupNamespaces[$group->namespace . '\\' . $group->class] = $group;
                    }
                }
            }
            foreach ($this->groups as $group) {
                if (!isset($groups[$group->name])) {
                    $groups[$group->name] = $group;
                }
            }
        }
        // 将同名字空间下 task 设置为同一个 taskGroup
        foreach ($this->tasks as $task) {
            if (empty($task->group)) {
                if ($task->namespace && isset($groupNamespaces[$task->namespace])) {
                    $task->group = $groupNamespaces[$task->namespace]->name;
                } else {
                    $class = $task->namespace ? $task->namespace . '\\' . $task->class
                        : $task->class;
                    if (isset($groupNamespaces[$class])) {
                        $task->group = $groupNamespaces[$class]->name;
                    }
                }
            }
        }
        $tasks = [];
        foreach ($this->tasks as $task) {
            $name = $task->getName();
            if (isset($tasks[$name])) {
                throw new Exception(sprintf(
                    "Task '{$name}' defined in '%s' conflict with '%s'",
                    json_encode($task),
                    json_encode($tasks[$name])
                ));
            }
            if (isset($groups[$name])) {
                throw new Exception(sprintf(
                    "Task '{$name}' defined in '%s' conflict with group '%s'",
                    json_encode($task),
                    json_encode($groups[$name])
                ));
            }
            $tasks[$name] = $task;
            if ($task->group) {
                $groups[$task->getGroupName()]->addTask($task);
            }
        }
        // 如果可能，创建无 module 前缀的任务
        foreach ($this->tasks as $task) {
            if ($task->module) {
                $name = $task->getSimpleName();
                if (!isset($tasks[$name])) {
                    $tasks[$name] = $task;
                }
            }
        }
        $this->tasksMap = $tasks;
        $this->groupsMap = $groups;
        $this->processed = true;
    }
    
    private function hasGroup($group)
    {
        return isset($this->groupsMap[$group]);
    }
    
    private function hasTask($task)
    {
        return isset($this->tasksMap[$task]);
    }

    private function getOptionValues($options)
    {
        $params = [];
        foreach ($options as $option) {
            $params[$option->name] = $option->value;
        }
        return $params;
    }

    private function getClassNamespace($class)
    {
        $pos = strrpos($class, '\\');
        if ($pos !== false) {
            return substr($class, 0, $pos);
        }
    }

    private function getClassName($class)
    {
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            return $class;
        } else {
            return substr($class, $pos+1);
        }
    }
    
    private function parseOptions($task)
    {
        if ($task->options) {
            return;
        }
        $class = $task->namespace ? $task->namespace . '\\' . $task->class : $task->class;
        if ($task->method) {
            $methodAnnotations = $this->annotations->getMethod($class, $task->method);
            if ($methodAnnotations) {
                foreach ($methodAnnotations as $annotation) {
                    $this->processAnnotation($task, $annotation);
                }
            }
        } else {
            $propAnnotations = $this->annotations->getProperties($class);
            if ($propAnnotations) {
                foreach ($propAnnotations as $prop => $annotations) {
                    foreach ($annotations as $annotation) {
                        $this->processAnnotation($task, $annotation, $prop);
                    }
                }
            }
        }
    }

    private function processAnnotation($task, $annotation, $name = null)
    {
        switch ($annotation->getName()) {
            case Router::OPTION:
                $task->options[] = $this->createOption($annotation->getArguments(), $name);
                break;
            case Router::ARGUMENT:
                $task->arguments[] = $this->createArgument($annotation->getArguments(), $name);
                break;
        }
    }

    private function createOption($args, $name)
    {
        if (!isset($args['name'])) {
            $args['name'] = $name;
        }
        if (empty($args['name'])) {
            throw new Exception("Option name is not defined for "
                                . $this->getName()
                                ." args=" . json_encode($args));
        }
        if (isset($args['type']) && $args['type'] == 'boolean') {
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

    private function createArgument($args, $name)
    {
        if (isset($args[0])) {
            $args['name'] = $args[0];
        }
        if (!isset($args['name'])) {
            $args['name'] = $name;
        }
        if (empty($args['name'])) {
            throw new Exception("Argument name is not defined for "
                                . $this->getName()
                                ." args=" . json_encode($args));
        }
        if (isset($args['default'])) {
            $args['value'] = $args['default'];
        }
        return new Argument($args);
    }
}
