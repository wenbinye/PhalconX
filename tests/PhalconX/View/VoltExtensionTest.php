<?php
namespace PhalconX\View;

use PhalconX\Test\TestCase;

/**
 * TestCase for VoltExtension
 */
class VoltExtensionTest extends TestCase
{
    function testCamelize()
    {
        $this->assertEquals(VoltExtension::camelize('get_title'), 'getTitle');
    }
}
