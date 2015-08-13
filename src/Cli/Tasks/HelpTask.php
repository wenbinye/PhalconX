<?php
namespace PhalconX\Cli\Tasks;

use PhalconX\Cli\Task;
use PhalconX\Cli\Router;
use PhalconX\Annotations\Cli\Task as TaskAnnotation;
use PhalconX\Annotations\Cli\Option;
use PhalconX\Exception;

/**
 * @Task
 */
class HelpTask extends Task
{
    /**
     * @Argument(type=array)
     */
    public $task;
    
    public function execute()
    {
        if ($this->task) {
            if (count($this->task) > 1) {
                $task = $this->task[0] . ' ' . $this->task[1];
            } else {
                $task = $this->task[0];
            }
            $this->showTaskHelp($task);
        } else {
            $this->showHelp();
        }
    }

    private function showTaskHelp($task)
    {
        $router = $this->router;
        $def = $router->getDefinition($task);
        if (!$def) {
            echo "Task '$task' does not exist!\n";
            $this->showHelp();
            exit(-1);
        }
        if ($def instanceof TaskAnnotation) {
            echo sprintf(
                "usage: %s %s %s%s\n\n",
                $router->getScriptName(),
                $task,
                $this->formatOptions($def->options),
                $this->formatArguments($def->arguments)
            );
            echo $this->formatDetail($def->options + $def->arguments), "\n";
        } else {
            if (!$def->tasks) {
                throw new Exception("No tasks in group " . $def);
            }
            echo sprintf(
                "usage: %s %s <task> [<args>]\n\n",
                $router->getScriptName(),
                $task
            );
            echo "可用命令有：\n";
            $tasks = $def->tasks;
            $len = max(array_map(function ($task) {
                        return strlen($task->name);
            }, $tasks));
            foreach ($tasks as $task) {
                printf('  %-' . $len . "s  %s\n", $task->name, $task->help);
            }
        }
    }
    
    private function showHelp()
    {
        $router = $this->router;
        $options = $router->getGlobalOptions();
        echo sprintf(
            "usage: %s %s<task> [<args>]\n\n",
            $router->getScriptName(),
            $this->formatOptions($options)
        );
        if ($options) {
            echo "全局选项：\n";
            echo $this->formatDetail($options), "\n\n";
        }
        echo "可用命令有：\n";
        $tasks = $router->getDefinitions();
        $tasks = $this->getTasksAndGroups($tasks);
        $len = max(array_map('strlen', array_keys($tasks)));
        foreach ($tasks as $name => $task) {
            if ($name == 'help') {
                continue;
            }
            printf('  %-' . $len . "s  %s\n", $name, $task->help);
        }
    }

    private function getTasksAndGroups($tasks)
    {
        $router = $this->router;
        $all = [];
        foreach ($tasks as $name => $task) {
            $pos = strpos($name, ' ');
            if ($pos === false) {
                $all[$name] = $task;
            } else {
                $group = substr($name, 0, $pos);
                $all[$group] = $router->getDefinition($group);
            }
        }
        // 删除重复的
        foreach ($all as $name => $task) {
            $pos = strpos($name, Router::SEPARATOR);
            if ($pos !== false) {
                $shortcut = substr($name, $pos+1);
                if (isset($all[$shortcut]) && $all[$shortcut]->getId() == $task->getId()) {
                    unset($all[$name]);
                }
            }
        }
        return $all;
    }
    
    private function formatOptions($options)
    {
        if (!$options) {
            return;
        }
        $all = [];
        foreach ($options as $opt) {
            $str = $this->getOptionSignal($opt);
            if (!$opt->required) {
                $str = '[' . $str . ']';
            }
            $all[]  = $str;
        }
        return implode(' ', $all) . ' ';
    }

    private function getOptionSignal($opt)
    {
        $str = '';
        if ($opt->short) {
            $str = '-'.$opt->short;
        }
        if ($opt->long) {
            $str .= $str ? '|' . '--'.$opt->long : '--'.$opt->long;
        }
        if (!$opt->optional) {
            $str .= ' ' . $opt->name;
        }
        return $str;
    }
    
    private function formatDetail($args)
    {
        $list = [];
        $len = 0;
        foreach ($args as $arg) {
            if ($arg instanceof Option) {
                $sig = $this->getOptionSignal($arg);
                $len = max($len, strlen($sig));
                $list[] = [$sig, $arg];
            } else {
                if ($arg->help) {
                    $len = max($len, strlen($arg->name));
                    $list[] = [$arg->name, $arg];
                }
            }
        }
        foreach ($list as $i => $item) {
            $help = $item[1]->help;
            if (isset($item[1]->default)) {
                $help .= " (default " . $item[1]->default . ')';
            }
            $list[$i] = sprintf('  %-' . $len . "s  %s", $item[0], $help);
        }
        return implode("\n", $list);
    }
    
    private function formatArguments($arguments)
    {
        if (!$arguments) {
            return;
        }
        $all = [];
        foreach ($arguments as $arg) {
            if ($arg->type == 'array') {
                $all[] = $arg->name . '...';
            } else {
                $all[] = $arg->required ? $arg->name : "[{$arg->name}]";
            }
        }
        return implode(' ', $all);
    }
}
