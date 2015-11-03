<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\Regex;

/**
 * TestCase for Boolean
 */
class IntegerTest extends TestCase
{
    protected static $annotationClass = Integer::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation();
        $this->assertTrue($annotation->getValidator($this->form) instanceof Regex);
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
            ['-32', 0],
            ['12', 0],
            ["abc1", 1],
        ];
    }
}
