<?php
namespace PhalconX\Enums;

use PhalconX\Test\TestCase;

class BooleanTest extends TestCase
{
    function testValueOf()
    {
        $this->assertEquals(Boolean::valueOf(1), Boolean::TRUE);
        $this->assertEquals(Boolean::valueOf("1"), Boolean::TRUE);
        $this->assertEquals(Boolean::valueOf('true'), Boolean::TRUE);
        $this->assertEquals(Boolean::valueOf(true), Boolean::TRUE);

        $this->assertEquals(Boolean::valueOf(0), Boolean::FALSE);
        $this->assertEquals(Boolean::valueOf("0"), Boolean::FALSE);
        $this->assertEquals(Boolean::valueOf('false'), Boolean::FALSE);
        $this->assertEquals(Boolean::valueOf(false), Boolean::FALSE);

        $this->assertNull(Boolean::valueOf(2));
        $this->assertNull(Boolean::valueOf('t'));
    }
}
