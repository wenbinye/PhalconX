<?php
namespace PhalconX\Di;

use PhalconX\Test\TestCase;
use Phalcon\Di;
use Phalcon\Config;

/**
 * TestCase for Injectable
 */
class InjectableTest extends TestCase
{
    private $service;

    public function setUp()
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

class MyService
{
    use Injectable;
}
