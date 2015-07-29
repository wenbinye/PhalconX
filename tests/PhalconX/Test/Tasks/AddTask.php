<?php
namespace PhalconX\Test\Tasks;

/**
 * @Command(help="Add file contents to the index")
 */
class AddTask extends BaseTask
{
    /**
     * @Option("-i", optional=true, help="Add modified contents interactively.")
     */ 
    public $interactive;

    /**
     * @Argument(help="Patterns of files to be added.")
     */
    public $patterns;
}
