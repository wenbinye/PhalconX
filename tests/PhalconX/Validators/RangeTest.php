<?php
namespace PhalconX\Validators;

/**
 * TestCase for Range
 */
class RangeTest extends BaseValidatorTest
{
    /**
     * @dataProvider dataProvider
     */
    public function testValidate($value, $args, $result)
    {
        $this->validation->add('int', new Range($args));
        $errors = $this->validation->validate(['int' => $value]);
        // print_r($errors);
        $this->assertEquals(count($errors), $result ? 0 : 1);
    }

    public function dataProvider()
    {
        return [
            [10, ['minimum' => 0, 'maximum' => 10], true],
            [10, ['minimum' => 0, 'maximum' => 10, 'exclusiveMaximum' => true], false],
            [0, ['minimum' => 0, 'maximum' => 10], true],
            [0, ['minimum' => 0, 'maximum' => 10, 'exclusiveMinimum' => true], false],
        ];
    }
}
