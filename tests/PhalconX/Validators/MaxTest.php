<?php
namespace PhalconX\Validators;

class MaxTest extends BaseValidatorTest
{
    public function testValidateOk()
    {
        $this->validation->add('age', new Max(['value' => 100]));
        $errors = $this->validation->validate(['age' => 10]);
        $this->assertEquals(count($errors), 0);
    }

    public function testValidateInvalid()
    {
        $this->validation->add('age', new Max(['value' => 100]));
        $errors = $this->validation->validate(['age' => 101]);
        $this->assertEquals(count($errors), 1);
        $error = $errors[0];
        $this->assertEquals($error->getField(), 'age');
        $this->assertEquals($error->getMessage(), 'The age must less than 100');
        // print_r($errors[0]);
    }
}
