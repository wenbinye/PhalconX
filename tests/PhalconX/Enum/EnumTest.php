<?php
namespace PhalconX\Enum;

use PhalconX\Test\TestCase;
use PhalconX\Test\Enum\Gender;
use PhalconX\Test\Enum\OnOff;

/**
 * TestCase for Enum
 */
class EnumTest extends TestCase
{
    public function testEquality()
    {
        $on = OnOff::fromValue('1');
        $onObj = OnOff::ON();
        // var_export([$on, $onObj]);
        $this->assertTrue($on === $onObj);
    }
    
    public function testName()
    {
        $this->assertEquals(Gender::MALE()->name(), 'MALE');
    }

    public function testValue()
    {
        $this->assertEquals(Gender::MALE()->value(), 'm');
    }

    public function testToString()
    {
        $this->assertEquals((string) Gender::MALE(), 'MALE');
    }

    public function testMagicGet()
    {
        $this->assertEquals(Gender::MALE()->name, 'MALE');
        $this->assertEquals(Gender::MALE()->value, 'm');
        $this->assertEquals(Gender::MALE()->description, '男');
    }

    public function testValues()
    {
        $this->assertEquals(Gender::values(), ['m', 'f']);
    }

    public function testNames()
    {
        $this->assertEquals(Gender::names(), ['MALE', 'FEMALE']);
    }

    public function testInstances()
    {
        $this->assertEquals(Gender::instances(), [
            Gender::MALE(), Gender::FEMALE()
        ]);
    }

    public function testNameOf()
    {
        $this->assertEquals(Gender::nameOf('m'), 'MALE');
    }

    public function testHasName()
    {
        $this->assertTrue(Gender::hasName('MALE'));
    }

    public function testValueOf()
    {
        $this->assertEquals(Gender::valueOf('MALE'), 'm');
    }

    public function testHasValue()
    {
        $this->assertTrue(Gender::hasValue('m'));
    }

    public function testFromName()
    {
        $this->assertEquals(Gender::fromName('MALE'), Gender::MALE());
    }

    public function testFromValue()
    {
        $this->assertEquals(Gender::fromValue('m'), Gender::MALE());
    }
}
