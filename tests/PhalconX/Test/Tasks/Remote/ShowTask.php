<?php
namespace PhalconX\Test\Tasks\Remote;

use PhalconX\Test\Tasks\BaseTask;

/**
 * @Task(help="Gives some information about the remote <name>")
 */
class ShowTask extends BaseTask
{
    /**
     * @Option("-n", help="Do not query remote heads")
     */
    public $noQuery;

    /**
     * @Argument(type=array, help="Remote to show")
     */
    public $remote;
}
