<?php
namespace PhalconX\Di;

use Phalcon\Di;
use Phalcon\Config;
use PhalconX\Test\TestCase;
use PhalconX\Test\Di\MyService;

/**
 * TestCase for Injectable
 */
class InjectableTest extends TestCase
{
    private $service;

    /**
     * @before
     */
    public function setupDi()
    {
        $di = new Di;
        $di['config'] = new Config;

        $service = new MyService;
        $service->setDi($di);
        $this->service = $service;
    }

    public function testGet()
    {
        $this->assertTrue($this->service->config instanceof Config);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testNotExistException()
    {
        // $this->setExpectedException('PHPUnit_Framework_Error_Notice');
        $this->assertNull($this->service->foo);
    }

    public function testNotExist()
    {
        $this->assertNull(@$this->service->foo);
    }
}
