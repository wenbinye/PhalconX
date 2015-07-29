<?php
namespace PhalconX\Test\Tasks;

use PhalconX\Cli\Task;

class BaseTask extends Task
{
    public function execute()
    {
        echo "run " . get_class($this) . " with arguments "
            . json_encode($this), "\n";
    }
}
