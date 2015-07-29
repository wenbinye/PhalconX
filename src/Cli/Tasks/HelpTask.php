<?php
namespace PhalconX\Cli\Tasks;

use PhalconX\Cli\Task;
use PhalconX\Cli\Router;
use PhalconX\Cli\TaskDefinition;

/**
 * @Command
 */
class HelpTask extends Task
{
    /**
     * @Argument(type=array)
     */
    public $command;
    
    public function execute()
    {
        if ($this->command) {
            $this->showCommandHelp($this->command);
        } else {
            $this->showHelp();
        }
    }

    private function showCommandHelp($command)
    {
        $router = $this->router;
        if (count($command) > 1) {
            $def = $router->getTaskDefinition($command[1], $command[0]);
            $command = $command[1] . ' ' . $command[0];
        } else {
            $def = $router->getTaskDefinition($command[0]);
            $command = $command[0];
        }
        if (!$def) {
            echo "Command '$command' does not exist!\n";
        }
        if ($def instanceof TaskDefinition) {
            echo sprintf(
                "usage: %s %s %s%s\n\n",
                $router->getScriptName(),
                $command,
                $this->formatOptions($def->options),
                $this->formatArguments($def->arguments)
            );
            echo $this->formatOptionDetail($def->options), "\n";
        } else {
            echo sprintf(
                "usage: %s %s <command> [<args>]\n\n",
                $router->getScriptName(),
                $command
            );
            echo "可用命令有：\n";
            $commands = $def->tasks;
            $len = 0;
            foreach ($commands as $name => $task) {
                $len = max(strlen($name), $len);
            }
            foreach ($commands as $name => $task) {
                printf('  %-' . $len . "s  %s\n", $name, $task->help);
            }
        }
    }
    
    private function showHelp()
    {
        $router = $this->router;
        $options = $router->getGlobalOptions();
        echo sprintf(
            "usage: %s %s<command> [<args>]\n\n",
            $router->getScriptName(),
            $this->formatOptions($options)
        );
        if ($options) {
            echo $this->formatOptionDetail($options), "\n";
        }
        echo "可用命令有：\n";
        $tasks = $router->getTaskDefinitions();
        $commands = $this->removePrefix($tasks);
        $len = 0;
        foreach ($commands as $name => $task) {
            $len = max(strlen($name), $len);
        }
        foreach ($commands as $name => $task) {
            if ($name == 'help') {
                continue;
            }
            printf('  %-' . $len . "s  %s\n", $name, $task->help);
        }
    }

    private function removePrefix($commands)
    {
        $all = [];
        $noprefix = [];
        foreach ($commands as $name => $task) {
            if (strpos($name, Router::SEPARATOR) === false) {
                $noprefix[$task->getId()] = 1;
                $all[$name] = $task;
            }
        }
        foreach ($commands as $name => $task) {
            $pos = strpos($name, Router::SEPARATOR);
            if ($pos !== false) {
                if (isset($noprefix[$task->getId()])) {
                    continue;
                }
                $short = substr($name, $pos+1);
                if (isset($all[$short])) {
                    $all[$name] = $task;
                } else {
                    $all[$short] = $task;
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
