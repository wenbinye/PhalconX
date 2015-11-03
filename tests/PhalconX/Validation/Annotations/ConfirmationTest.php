<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\Confirmation as ConfirmationValidator;

/**
 * TestCase for Boolean
 */
class ConfirmationTest extends TestCase
{
    protected static $annotationClass = Confirmation::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation(['with' => 'foo']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof ConfirmationValidator);
    }

    /**
     * @dataProvider values
     */
    public function testDefaultProperty($value, $expect)
    {
        $validator = $this->getAnnotation(['foo'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value, 'foo' => 'foo']);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    /**
     * @dataProvider values
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation(['with' => 'foo'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value, 'foo' => 'foo']);
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
