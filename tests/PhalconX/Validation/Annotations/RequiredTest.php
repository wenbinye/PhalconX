<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\PresenceOf;

/**
 * TestCase for Required
 */
class RequiredTest extends TestCase
{
    protected static $annotationClass = Required::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation();
        $this->assertTrue($annotation->getValidator($this->form) instanceof PresenceOf);
    }

    /**
     * @dataProvider requireds
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation()->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function requireds()
    {
        return [
            [1, 0],
            [0, 0],
            ["", 1],
        ];
    }
}
