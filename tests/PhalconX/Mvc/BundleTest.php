<?php
namespace PhalconX\Mvc;

use PhalconX\Test\TestCase;

/**
 * TestCase for AbstractBundle
 */
class BundleTest extends TestCase
{
    public function testRegisterServices()
    {
        
    }
}

class MyBundle extends AbstractBundle
{
    /**
     * @Singleton
     */
    public function provideFooService($di, $args)
    {
        
    }
}
