<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use PhalconX\Validation\Validators\Min as MinValidator;

/**
 * TestCase for Boolean
 */
class MinTest extends TestCase
{
    protected static $annotationClass = Min::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation(['value' => '10']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof MinValidator);
    }

    /**
     * @dataProvider exclusive
     */
    public function testValidateExclusive($value, $expect)
    {
        $validator = $this->getAnnotation(['value' => 10, 'exclusive' => true])
            ->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    /**
     * @dataProvider values
     */
    public function testDefaultProperty($value, $expect)
    {
        $validator = $this->getAnnotation([10])
            ->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    /**
     * @dataProvider values
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation(['value' => 10])
            ->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function values()
    {
        return [
            ['8', 1],
            ['10', 0],
            ["11", 0],
        ];
    }

    public function exclusive()
    {
        return [
            ['8', 1],
            ['10', 1],
            ["11", 0],
        ];
    }
}
