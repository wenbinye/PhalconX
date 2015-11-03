<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use PhalconX\Validation\Validators\Datetime as DatetimeValidator;

/**
 * TestCase for Datetime
 */
class DatetimeTest extends TestCase
{
    protected static $annotationClass = Datetime::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation();
        $this->assertTrue($annotation->getValidator($this->form) instanceof DatetimeValidator);
    }

    /**
     * @dataProvider datetimes
     */
    public function testValidate($value, $pattern, $expect)
    {
        $validator = $this->getAnnotation([$pattern])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function datetimes()
    {
        return [
            ["2015-07-01 00:00:00", null, 0],
            ["2015-07-01", 'Y-m-d', 0],
            ["2015-07-01", null, 1],
        ];
    }
}
