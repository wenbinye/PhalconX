<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use PhalconX\Validation\Validators\IsArray as IsArrayValidator;

/**
 * TestCase for Boolean
 */
class IsArrayTest extends TestCase
{
    protected static $annotationClass = IsArray::class;

    public function testValidatorElement()
    {
        $annotation = $this->getAnnotation(['element' => 'integer']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof IsArrayValidator);
    }

    public function testValidator()
    {
        $annotation = $this->getAnnotation();
        $this->assertTrue($annotation->getValidator($this->form) instanceof IsArrayValidator);
    }

    /**
     * @dataProvider values
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation()->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = " . json_encode($value));
    }

    public function values()
    {
        return [
            [[], 0],
            [['12'], 0],
            ["abc1", 1],
        ];
    }

    /**
     * @dataProvider intArray
     */
    public function testValidateType($value, $expect, $message, $field)
    {
        $validator = $this->getAnnotation(['element' => 'integer'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = " . json_encode($value));
        if (count($errors)) {
            $error = $errors[0];
            $this->assertEquals($error->getMessage(), $message);
            $this->assertEquals($error->getField(), $field);
        }
    }

    public function intArray()
    {
        return [
            [[], 0, null, null],
            [['12'], 0, null, null],
            [["abc1"], 1, 'Field value[0] does not match the required format', 'value[0]'],
        ];
    }
}
