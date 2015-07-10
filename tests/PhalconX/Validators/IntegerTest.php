<?php
namespace PhalconX\Validators;

class IntegerTest extends BaseValidatorTest
{
    /**
     * @dataProvider dataProvider
     */
    public function testValidate($value, $result)
    {
        $this->validation->add('int', new Integer());
        $errors = $this->validation->validate(['int' => $value]);
        $this->assertEquals(count($errors), $result ? 0 : 1);
    }

    public function dataProvider()
    {
        return [
            [123, true],
            ['123', true],
            ['-123', true],
            ['+123', true],
            ['a', false]
        ];
    }
}
