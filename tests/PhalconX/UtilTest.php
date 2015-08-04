<?php
namespace PhalconX;

use Phalcon\Mvc\RouterInterface;
use PhalconX\Test\TestCase;
use Phalcon\Logger\Adapter\File;

/**
 * TestCase for Util
 */
class UtilTest extends TestCase
{
    public function testServiceDefault()
    {
        $router = Util::service('router');
        $this->assertTrue($router instanceof RouterInterface);
    }

    public function testServiceOptions()
    {
        $options = array('router' => 1);
        $router = Util::service('router', $options);
        $this->assertEquals($router, $options['router']);
    }

    public function testTemplate()
    {
        $ret = Util::template("hello, {name}", ['name' => 'world']);
        $this->assertEquals($ret, 'hello, world');

        $ret = Util::template("Hello, {name}!\nByebye, {name}!", ['name' => 'world']);
        $this->assertEquals($ret, "Hello, world!\nByebye, world!");
    }

    public function testRenewLogger()
    {
        // $di = \Phalcon\Di::getDefault();
        // $di['logger'] = function() {
        //     return new File('/tmp/test.log');
        // };
        // $logger = Util::service('logger');
        // $newlogger = Util::renewLogger();
        // $newlogger->info("test");
        // $logger->info("test");
        // print_r($logger);
        // print_r($newlogger);
    }
}
