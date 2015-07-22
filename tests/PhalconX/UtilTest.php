<?php
namespace PhalconX;

use Phalcon\Mvc\RouterInterface;
use PhalconX\Test\TestCase;

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

    function testTemplate()
    {
        $ret = Util::template("hello, {name}", ['name' => 'world']);
        $this->assertEquals($ret, 'hello, world');

        $ret = Util::template("Hello, {name}!\nByebye, {name}!", ['name' => 'world']);
        $this->assertEquals($ret, "Hello, world!\nByebye, world!");
    }
}
