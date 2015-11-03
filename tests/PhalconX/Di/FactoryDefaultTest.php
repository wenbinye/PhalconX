<?php
namespace PhalconX\Di;

use PhalconX\Test\TestCase;
use PhalconX\Test\Di\MyServiceProvider;

/**
 * TestCase for FactoryDefault
 */
class FactoryDefaultTest extends TestCase
{
    public function testGet()
    {
        $di = new FactoryDefault;
        $di->autoload(["fs"], MyServiceProvider::class);
        $di->autoload(["finder"], MyServiceProvider::class, false);
        $fs = $di->getFs();
        $this->assertTrue(isset($fs->fs));
        $fs2 = $di->getFs();
        $this->assertTrue($fs === $fs2);

        $finder = $di->getFinder();
        $finder2 = $di->getFinder();
        $this->assertFalse($finder === $finder2);
    }
}
