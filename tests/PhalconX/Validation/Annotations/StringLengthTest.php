<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\StringLength as StringLengthValidator;

/**
 * TestCase for Boolean
 */
class StringLengthTest extends TestCase
{
    protected static $annotationClass = StringLength::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation(['max' => '10']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof StringLengthValidator);
    }

    /**
     * @dataProvider values
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation(['min' => 2, 'max' => 10])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function values()
    {
        return [
            ['一二三四五一二三四五', 0],
            ['0123456789', 0],
            ["01234567891", 1],
            ['一二三四五一二三四五一', 1],

            ['一', 1],
            ['1', 1],
            ['一2', 0],
            ['12', 0]
        ];
    }
}
