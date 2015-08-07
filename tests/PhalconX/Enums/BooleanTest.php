<?php
namespace PhalconX\Enums;

use PhalconX\Exception;
use PhalconX\Test\TestCase;

class BooleanTest extends TestCase
{
    /**
     * @dataProvider booleans
     */
    public function testValueOf($value, $expect)
    {
        $this->assertEquals(Boolean::valueOf($value), $expect);
    }

    public function booleans()
    {
        return [
            [1, Boolean::TRUE()],
            ["1", Boolean::TRUE()],
            ['true', Boolean::TRUE()],
            [true, Boolean::TRUE()],
            [0, Boolean::FALSE()],
            ["0", Boolean::FALSE()],
            ['false', Boolean::FALSE()],
            [false, Boolean::FALSE()],
        ];
    }

    /**
     * @dataProvider invalid
     */
    public function testNonValid($value)
    {
        try {
            Boolean::valueOf($value);
            $this->fail();
        } catch (Exception $e) {
        }
    }

    public function invalid()
    {
        return [
            ['t'],
            [2]
        ];
    }
}
