<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\Numericality;

/**
 * TestCase for Boolean
 */
class NumberTest extends TestCase
{
    protected static $annotationClass = Number::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation();
        $this->assertTrue($annotation->getValidator($this->form) instanceof Numericality);
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
            ['12', 0],
            ['12.1', 0],
            ['-1', 0],
            ['-12.23', 0],
            ["bar", 1],
        ];
    }
}
