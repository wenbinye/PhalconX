<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use PhalconX\Validation\Validators\IsA as IsAValidator;
use PhalconX\Test\Validation\User;

/**
 * TestCase for Boolean
 */
class IsATest extends TestCase
{
    protected static $annotationClass = IsA::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation(['User']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof IsAValidator);
    }

    /**
     * @dataProvider values
     */
    public function testValidate($value, $expect, $message, $field)
    {
        $validator = $this->getAnnotation(['User'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        // print_r($errors);
        $this->assertEquals(count($errors), $expect, "value = $value");
        if (count($errors)) {
            $error = $errors[0];
            $this->assertEquals($error->getMessage(), $message);
            $this->assertEquals($error->getField(), $field);
        }
    }

    public function values()
    {
        return [
            ['user', 1, 'Field value is not instance of PhalconX\Test\Validation\User', 'value'],
            [new User, 1, 'Field name is required', 'value.name'],
            [$this->newUser(['name' => 'john']), 0, null, null],
        ];
    }

    private function newUser($args)
    {
        $user = new User;
        foreach ($args as $key => $val) {
            $user->$key = $val;
        }
        return $user;
    }
}
