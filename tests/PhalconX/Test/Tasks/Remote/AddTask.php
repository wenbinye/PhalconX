<?php
namespace PhalconX\Test\Tasks\Remote;

use PhalconX\Test\Tasks\BaseTask;

/**
 * @CommandGroup("remote", help="Manage set of tracked repositories")
 * @Command(group="remote", help="Adds a remote")
 */
class AddTask extends BaseTask
{
    /**
     * @Option("-t", type=string, help="Track only a specific branch")
     */
    public $branch;
    
    /**
     * @Argument(help="Remote name")
     */
    public $name;

    /**
     * @Argument(help="Remote repository to add")
     */
    public $url;
}
