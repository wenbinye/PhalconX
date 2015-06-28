<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Validator\Digit;

class ArrayTest extends BaseValidatorTest
{
    public function testValidateOk()
    {
        $this->validation->add('numbers', new IsArray());
        $errors = $this->validation->validate(['numbers' => array(10)]);
        $this->assertEquals(count($errors), 0);
    }

    public function testValidateInvalid()
    {
        $this->validation->add('numbers', new IsArray(['element' => new Digit]));
        $errors = $this->validation->validate(['numbers' => array(10)]);
        $this->assertEquals(count($errors), 1);
        // print_r($errors);
        $error = $errors[0];
        $this->assertEquals($error->getField(), 'numbers[0]');
    }
}
