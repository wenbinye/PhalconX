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
}
