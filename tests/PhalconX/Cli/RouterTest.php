<?php
namespace PhalconX\Cli;

use PhalconX\Test\TestCase;

/**
 * TestCase for Router
 */
class RouterTest extends TestCase
{
    public function testScan()
    {
        $router = new Router;
        $router->scan(__DIR__.'/../Test/Tasks');
        // print_r($router);
        $router->handle(["./cli", "remote", "add", "-t", "master", "origin", "url"]);
        // print_r($router);
        $this->assertEquals($router->getNamespaceName(), 'PhalconX\Test\Tasks\Remote');
        $this->assertEquals($router->getTaskName(), 'AddTask');
        $this->assertEquals($router->getActionName(), 'execute');
    }
}
