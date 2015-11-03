<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\InclusionIn;
use PhalconX\Validation\Validators\InclusionInModel;
use PhalconX\Test\Enum\Gender;

/**
 * TestCase for Url
 */
class EnumTest extends TestCase
{
    protected static $annotationClass = Enum::class;

    public function testValidatorArgsDomain()
    {
        $annotation = $this->getAnnotation(['domain' => ['a', 'b']]);
        $this->assertTrue($annotation->getValidator($this->form) instanceof InclusionIn);
    }

    public function testValidatorArgsEnumClassValues()
    {
        $annotation = $this->getAnnotation(['Gender.values']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof InclusionIn);
    }

    public function testValidatorArgsEnumClass()
    {
        $annotation = $this->getAnnotation(['Gender']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof InclusionIn);
    }

    public function testValidatorArgsModel()
    {
        $annotation = $this->getAnnotation([['a', 'b']]);
        $this->assertTrue($annotation->getValidator($this->form) instanceof InclusionIn);
    }

    /**
     * @dataProvider enums
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation(['Gender'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    /**
     * @dataProvider enumValues
     */
    public function testValidateValues($value, $expect)
    {
        $validator = $this->getAnnotation(['Gender.values'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function enums()
    {
        return [
            ['male', 0],
            ["m", 1]
        ];
    }

    public function enumValues()
    {
        return [
            ['male', 1],
            ["m", 0]
        ];
    }
}
