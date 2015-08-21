<?php
namespace PhalconX\Cli;

use Phalcon\Text;
use Phalcon\Validation\Message;
use PhalconX\Exception;
use PhalconX\Util;
use PhalconX\Exception\ValidationException;
use PhalconX\Annotations\ContextType;
use PhalconX\Annotations\Cli\Task as TaskAnnotation;
use PhalconX\Annotations\Cli\TaskGroup;
use PhalconX\Annotations\Cli\Option;
use PhalconX\Annotations\Cli\Argument;

class Router
{
    const SEPARATOR = ':';
    const EXECUTE_METHOD = 'execute';

    /**
     * @var boolean
     */
    private $matched;
    /**
     * @var string
     */
    private $scriptName;
    /**
     * @var TaskAnnotation
     */
    private $task;
    /**
     * @var TaskAnnotation[]
     */
    private $tasks;
    /**
     * @var TaskGroup[]
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
    /**
     * @var Map<String, TaskAnnotation>
     */
    private $tasksMap;
    /**
     * @var Map<String, TaskGroup>
     */
    private $groupsMap;
    
    private $reflection;
    private $annotations;
    private $logger;

    private static $groupCache;

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
        $this->addTaskGroups($this->annotations->scan($dir, TaskGroup::CLASS), $module);
        $this->addTasks($this->annotations->scan($dir, TaskAnnotation::CLASS), $module);
        $this->proccesed = false;
    }

    public function addFileResource($file, $module = null)
    {
        $this->addTaskGroups($this->annotations->scanFile($dir, TaskGroup::CLASS), $module);
        $this->addTasks($this->annotations->scanFile($file, TaskAnnotation::CLASS), $module);
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

    /**
     * @return boolean
     */
    public function wasMatched()
    {
        return $this->matched;
    }

    /**
     * @return string current php script name
     */
    public function getScriptName()
    {
        return $this->scriptName;
    }

    /**
     * @return Option[]
     */
    public function getGlobalOptions()
    {
        return $this->globalOptions;
    }

    /**
     * @return array values of global options
     */
    public function getGlobalParams()
    {
        return $this->getOptionValues($this->globalOptions);
    }

    /**
     * @return array values of task options and arguments
     */
    public function getParams()
    {
        $params = $this->getOptionValues($this->task->options);
        foreach ($this->task->arguments as $arg) {
            $params[$arg->name] = $arg->value;
        }
        return $params;
    }

    /**
     * @return string task namespace
     */
    public function getNamespaceName()
    {
        return $this->task->namespace;
    }

    /**
     * @return string task class name
     */
    public function getTaskName()
    {
        return $this->task->class;
    }

    /**
     * @return string task method name
     */
    public function getActionName()
    {
        return $this->task->method;
    }

    /**
     * @param string task name
     * @return TaskAnnotation
     */
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

    /**
     * @return Map<String, TaskAnnotation>
     */
    public function getDefinitions()
    {
        $this->processTasks();
        return $this->tasksMap;
    }

    /**
     * @param TaskGroup[] $annotations
     * @param string $module
     */
    private function addTaskGroups($annotations, $module)
    {
        foreach ($annotations as $annotation) {
            $context = $annotation->getContext();
            list($annotation->namespace, $annotation->class)
                = Util::splitClassName($context->class);
            if (!$annotation->name) {
                if ($this->taskSuffix && Text::endsWith($annotation->class, $this->taskSuffix)) {
                    $annotation->name = lcfirst(substr($annotation->class, 0, -strlen($this->taskSuffix)));
                } else {
                    throw new Exception("Group name is required which defined in '{$context->class}'");
                }
            }
            $annotation->module = $module;
            $this->groups[] = $annotation;
        }
    }

    /**
     * @param TaskAnnotation[] $annotations
     * @param string $module
     */
    private function addTasks($annotations, $module)
    {
        foreach ($annotations as $annotation) {
            $context = $annotation->getContext();
            $annotation->module = $module;
            list($annotation->namespace, $annotation->class)
                = Util::splitClassName($context->class);
            if ($annotation->isClass()) {
                $annotation->method = self::EXECUTE_METHOD;
            } elseif ($annotation->isMethod()) {
                $annotation->method = $context->method;
            } else {
                throw new Exception("Unexpected annotation for task at class "
                                    ."'{$context->class}' property {$context->property}");
            }
            if (!isset($annotation->name)) {
                if ($annotation->isClass()) {
                    if ($this->taskSuffix && !Text::endsWith($annotation->class, $this->taskSuffix)) {
                        throw new Exception("Task handler '$context->class' not match suffix '{$this->taskSuffix}'");
                    }
                    $annotation->name = Text::uncamelize(substr($annotation->class, 0, -strlen($this->taskSuffix)));
                } else {
                    if ($this->actionSuffix && !Text::endsWith($context->method, $this->actionSuffix)) {
                        throw new Exception("Task handler '{$context->class}::{$context->method}'"
                                            ." not match suffix '{$this->actionSuffix}'");
                    }
                    $annotation->name = Text::uncamelize(substr($context->method, 0, -strlen($this->actionSuffix)));
                }
            }
            $annotation->name = str_replace('_', '-', $annotation->name);
            $this->tasks[] = $annotation;
        }
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
                if (empty($arguments)) {
                    break;
                }
                if ($argument->type == 'array') {
                    $argument->value = $arguments;
                    break;
                } else {
                    $argument->value = array_shift($arguments);
                }
            }
        }

        $group = new Message\Group;
        foreach ($this->task->options as $opt) {
            if ($opt->required && !isset($opt->value)) {
                $group->appendMessage(new Message("缺少选项 {$opt->name}"));
            }
        }
        foreach ($this->task->arguments as $arg) {
            if ($arg->required && !isset($arg->value)) {
                $group->appendMessage(new Message("缺少参数 {$arg->name}"));
            }
        }
        if (count($group)) {
            throw new ValidationException($group);
        }
    }

    private function matchTask()
    {
        if (empty($this->arguments)) {
            if ($this->hasTask($this->defaultTask)) {
                $this->setMatchedTask($this->defaultTask);
            }
        } else {
            $arg = array_shift($this->arguments);
            if ($this->hasGroup($arg)) {
                $task = $arg . ' ' . array_shift($this->arguments);
                if ($this->hasTask($task)) {
                    $this->setMatchedTask($task);
                }
            } elseif ($this->hasTask($arg)) {
                $this->setMatchedTask($arg);
            }
        }
    }

    private function setMatchedTask($task)
    {
        $this->matched = true;
        $this->task = $this->tasksMap[$task];
        $this->parseOptions($this->task);
    }

    private function processTasks()
    {
        if ($this->processed || !$this->tasks) {
            return;
        }
        $this->processTaskGroups();
        $tasks = [];
        foreach ($this->tasks as $task) {
            $name = $task->getName();
            if (isset($tasks[$name])) {
                throw new Exception(sprintf(
                    "Task '{$name}' defined in %s conflict with %s",
                    $task,
                    $tasks[$name]
                ));
            }
            if (isset($this->groupsMap[$name])) {
                throw new Exception(sprintf(
                    "Task '{$name}' defined in %s conflict with group %s",
                    $task,
                    $groups[$name]
                ));
            }
            $tasks[$name] = $task;
            if ($task->group) {
                $this->groupsMap[$task->getGroupName()]->addTask($task);
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
        $this->processed = true;
    }

    private function processTaskGroups()
    {
        $groups = [];
        $groupNamespaces = [];
        if ($this->groups) {
            foreach ($this->groups as $group) {
                $name = $group->getName();
                if (isset($groups[$name])) {
                    throw new Exception(sprintf(
                        "Group '{$name}' defined in %s conflict with %s",
                        $group,
                        $groups[$name]
                    ));
                }
                $groups[$name] = $group;
                if (Text::endsWith($group->class, $this->taskSuffix)) {
                    $groupNamespaces[$group->getClass()] = $group;
                } elseif ($group->namespace) {
                    $groupNamespaces[$group->namespace] = $group;
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
                if (isset($groupNamespaces[$task->getClass()])) {
                    $task->group = $groupNamespaces[$task->getClass()]->name;
                } elseif ($task->namespace && isset($groupNamespaces[$task->namespace])) {
                    $task->group = $groupNamespaces[$task->namespace]->name;
                }
            }
        }
        $this->groupsMap = $groups;
    }

    private function parseOptions(TaskAnnotation $task)
    {
        if ($task->options) {
            return;
        }
        $clz = $task->getClass();
        $defaults = [];
        $annotations = $this->annotations->get($clz);
        $propertiesAnnotations = $annotations->filter([
            'propertiesOnly' => true,
            'isa' => Argument::CLASS
        ]);
        if ($propertiesAnnotations) {
            $refl = new \ReflectionClass($clz);
            $defaults = $refl->getDefaultProperties();
        }
        if ($task->isMethod()) {
            $methodAnnotations = $annotations->filter([
                'isa' => Argument::CLASS,
                'method' => $task->getMethod()
            ]);
            $annotations = $methodAnnotations->merge($propertiesAnnotations);
        } else {
            $annotations = $propertiesAnnotations;
        }
        foreach ($annotations as $annotation) {
            if (!$annotation->name) {
                if ($annotation->isProperty()) {
                    $annotation->name = $annotation->getProperty();
                } else {
                    throw new Exception(sprintf(
                        "%s name is not defined for %s",
                        ($annotation instanceof Option ? 'Option' : 'Argument'),
                        $annotation
                    ));
                }
            }
            if (isset($defaults[$annotation->name])
                && !isset($annotation->default)) {
                $annotation->default = $defaults[$annotation->name];
            }
            $annotation->value = $annotation->default;
            if ($annotation instanceof Option) {
                $task->options[] = $annotation;
            } else {
                $task->arguments[] = $annotation;
            }
        }
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

    public function getDefaultTask()
    {
        return $this->defaultTask;
    }

    public function setDefaultTask($defaultTask)
    {
        $this->defaultTask = $defaultTask;
        return $this;
    }

    public function getTaskSuffix()
    {
        return $this->taskSuffix;
    }

    public function setTaskSuffix($taskSuffix)
    {
        $this->taskSuffix = $taskSuffix;
        return $this;
    }

    public function getActionSuffix()
    {
        return $this->actionSuffix;
    }

    public function setActionSuffix($actionSuffix)
    {
        $this->actionSuffix = $actionSuffix;
        return $this;
    }

    public function getReflection()
    {
        return $this->reflection;
    }

    public function setReflection($reflection)
    {
        $this->reflection = $reflection;
        return $this;
    }

    public function getAnnotations()
    {
        return $this->annotations;
    }

    public function setAnnotations($annotations)
    {
        $this->annotations = $annotations;
        return $this;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
