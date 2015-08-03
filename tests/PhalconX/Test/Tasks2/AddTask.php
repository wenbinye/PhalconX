<?php
namespace PhalconX\Test\Tasks2;

use PhalconX\Cli\Task;

/**
 * @Task(help="t2 add task")
 */
class AddTask extends Task
{
    public function execute()
    {
        echo "task2 add\n";
    }
}
