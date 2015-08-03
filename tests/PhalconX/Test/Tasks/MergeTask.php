<?php
namespace PhalconX\Test\Tasks;

class MergeTask
{
    /**
     * @Task(help="Join two or more development histories together")
     * @Option("-m", name=msg, help="commit message")
     * @Argument(name=commit)
     */
    public function mergeAction($options)
    {
        echo "run " . get_class($this) . " with arguments "
            . json_encode($options), "\n";
    }

    /**
     * @Task(help="Run a three-way file merge")
     * @Argument(name=current_file)
     * @Argument(name=base_file)
     * @Argument(name=other_file)
     */
    public function mergeFileAction($options)
    {
        echo "run " . get_class($this) . " with arguments "
            . json_encode($options), "\n";
    }
}
