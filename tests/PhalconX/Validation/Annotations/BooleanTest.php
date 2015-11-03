<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use PhalconX\Validation\Validators\Boolean as BooleanValidator;

/**
 * TestCase for Boolean
 */
class BooleanTest extends TestCase
{
    protected static $annotationClass = Boolean::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation();
        $this->assertTrue($annotation->getValidator($this->form) instanceof BooleanValidator);
    }

    /**
     * @dataProvider values
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation()->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function values()
    {
        return [
            [1, 0],
            ["1", 0],
            ['true', 0],
            [true, 0],
            [0, 0],
            ["0", 0],
            ['false', 0],
            [false, 0],
            ['t', 1],
            [2, 1]
        ];
    }
}
