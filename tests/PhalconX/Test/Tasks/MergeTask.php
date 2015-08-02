<?php
namespace PhalconX\Test\Tasks;

class MergeTask
{
    /**
     * @Task
     * @Option("-m", name=msg, help="commit message")
     * @Argument(name=commit)
     */
    public function mergeAction($options)
    {
        echo "run " . get_class($this) . " with arguments "
            . json_encode($options), "\n";
    }
}
