<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use PhalconX\Validation\Validators\Range as RangeValidator;

/**
 * TestCase for Boolean
 */
class RangeTest extends TestCase
{
    protected static $annotationClass = Range::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation(['max' => '10']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof RangeValidator);
    }

    /**
     * @dataProvider exclusiveMax
     */
    public function testValidateExclusiveMax($value, $expect)
    {
        $validator = $this->getAnnotation(['max' => 10, 'exclusiveMaximum' => true])
            ->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    /**
     * @dataProvider maxValues
     */
    public function testValidateMax($value, $expect)
    {
        $validator = $this->getAnnotation(['max' => 10])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }


    /**
     * @dataProvider exclusiveMin
     */
    public function testValidateExclusiveMin($value, $expect)
    {
        $validator = $this->getAnnotation(['min' => 10, 'exclusiveMinimum' => true])
            ->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    /**
     * @dataProvider minValues
     */
    public function testValidateMin($value, $expect)
    {
        $validator = $this->getAnnotation(['min' => 10])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function maxValues()
    {
        return [
            ['8', 0],
            ['10', 0],
            ["11", 1],
        ];
    }

    public function exclusiveMax()
    {
        return [
            ['8', 0],
            ['10', 1],
            ["11", 1],
        ];
    }

    public function minValues()
    {
        return [
            ['8', 1],
            ['10', 0],
            ["11", 0],
        ];
    }

    public function exclusiveMin()
    {
        return [
            ['8', 1],
            ['10', 1],
            ["11", 0],
        ];
    }
}
