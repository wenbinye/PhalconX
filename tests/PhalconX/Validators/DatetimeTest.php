<?php
namespace PhalconX\Validators;

/**
 * TestCase for Datetime
 */
class DatetimeTest extends BaseValidatorTest
{
    /**
     * @dataProvider dataProvider
     */
    public function testValidate($pattern, $date, $result)
    {
        $this->validation->add('date', new Datetime(['pattern' => $pattern]));
        $errors = $this->validation->validate(['date' => $date]);
        $this->assertEquals(count($errors), $result ? 0 : 1);
    }

    public function dataProvider()
    {
        return [
            ['Y-m-d', '2015-08-11', true],
            ['Y-m-d', '2015/08/11', false],
            ['Y-m-d', '2015-08-32', false],
        ];
    }
}
