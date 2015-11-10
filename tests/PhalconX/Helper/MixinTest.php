<?php
namespace PhalconX\Helper;

use PhalconX\Test\TestCase;

/**
 * TestCase for Mixin
 */
class MixinTest extends TestCase
{
    public function testCreate()
    {
        $mix = Mixin::create(new A, new B);
        $this->assertEquals($mix->foo(), 'foo');
        $this->assertEquals($mix->bar(), 'foobar');
    }
}

class A
{
    public function foo()
    {
        return 'foo';
    }
}

class B
{
    public function bar($o)
    {
        return $o->foo() . 'bar';
    }
}