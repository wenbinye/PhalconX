<?php
namespace PhalconX\Annotations\Cli;

use PhalconX\Test\TestCase;

/**
 * TestCase for TaskGroup
 */
class TaskGroupTest extends TestCase
{
    public function testConstruct()
    {
        $t = new TaskGroup(["cli", 'help' => 'desc']);
        $this->assertEquals($t->name, 'cli');
    }
}
