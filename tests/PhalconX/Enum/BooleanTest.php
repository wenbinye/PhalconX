<?php
namespace PhalconX\Enum;

use PhalconX\Exception;
use PhalconX\Test\TestCase;

class BooleanTest extends TestCase
{
    /**
     * @dataProvider booleans
     */
    public function testValueOf($value, $expect)
    {
        $val = Boolean::valueOf($value);
        // var_export([$value, $val, $expect]);
        $this->assertEquals($val, $expect);
    }

    public function booleans()
    {
        return [
            [1, true],
            ["1", true],
            ['true', true],
            [true, true],
            [0, false],
            ["0", false],
            ['false', false],
            [false, false],
            ['t', null],
            [2, null]
        ];
    }
}
