<?php
namespace PhalconX\Cli\Tasks;

use PhalconX\Cli\Task;
use PhalconX\Cli\Router;
use PhalconX\Cli\Task\Definition;

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
        }
        if ($def instanceof Definition) {
            echo sprintf(
                "usage: %s %s %s%s\n\n",
                $router->getScriptName(),
                $task,
                $this->formatOptions($def->options),
                $this->formatArguments($def->arguments)
            );
            echo $this->formatOptionDetail($def->options), "\n";
        } else {
            if (!$def->tasks) {
                throw new Exception("No tasks in group " . json_encode($def));
            }
            echo sprintf(
                "usage: %s %s <task> [<args>]\n\n",
                $router->getScriptName(),
                $task
            );
            echo "可用命令有：\n";
            $tasks = $def->tasks;
            $len = 0;
            foreach ($tasks as $task) {
                $len = max(strlen($task->name), $len);
            }
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
            echo $this->formatOptionDetail($options), "\n";
        }
        echo "可用命令有：\n";
        $tasks = $router->getDefinitions();
        $tasks = $this->getTasksAndGroups($tasks);
        $len = 0;
        foreach ($tasks as $name => $task) {
            $len = max(strlen($name), $len);
        }
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
    
    private function formatOptionDetail($options)
    {
        if (!$options) {
            return;
        }
        $list = [];
        $len = 0;
        foreach ($options as $opt) {
            $sig = $this->getOptionSignal($opt);
            $len = max($len, $sig);
            $list[] = [$sig, $opt->help];
        }
        foreach ($list as $i => $item) {
            $list[$i] = sprintf('  %-' . $len . "s  %s\n", $item[0], $item[1]);
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
                $all[] = $arg->name;
            }
        }
        return implode(' ', $all);
    }
}
