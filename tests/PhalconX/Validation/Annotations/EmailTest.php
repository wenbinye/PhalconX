<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\Email as EmailValidator;

/**
 * TestCase for Email
 */
class EmailTest extends TestCase
{
    protected static $annotationClass = Email::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation();
        $this->assertTrue($annotation->getValidator($this->form) instanceof EmailValidator);
    }

    /**
     * @dataProvider emails
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation()->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function emails()
    {
        return [
            ['foo', 1],
            ["bar@c.a", 0],
        ];
    }
}
