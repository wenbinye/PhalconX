<?php
namespace PhalconX\Validation;

use PhalconX\Test\TestCase;
use PhalconX\Test\Validation\User;
use PhalconX\Exception\ValidationException;

/**
 * TestCase for Form
 */
class FormValidationTest extends TestCase
{
    private $form;

    public function setUp()
    {
        $this->form = new Form;
    }

    public function testValidateName()
    {
        try {
            $user = new User;
            $this->form->validate($user);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertEquals($errors[0]->getField(), 'name');
        }
    }

    public function testValidateId()
    {
        $user = new User;
        $user->id = 10;
        $user->name = 'john';
        $this->form->validate($user);
    }
}
