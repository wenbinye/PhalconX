<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\Identical as IdenticalValidator;

/**
 * TestCase for Boolean
 */
class IdenticalTest extends TestCase
{
    protected static $annotationClass = Identical::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation(['value' => 'foo']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof IdenticalValidator);
    }

    /**
     * @dataProvider values
     */
    public function testDefaultProperty($value, $expect)
    {
        $validator = $this->getAnnotation(['foo'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    /**
     * @dataProvider values
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation(['accepted' => 'foo'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function values()
    {
        return [
            ['foo', 0],
            ["bar", 1],
        ];
    }
}
