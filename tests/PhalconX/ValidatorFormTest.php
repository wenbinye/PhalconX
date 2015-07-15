<?php
namespace PhalconX;

use Phalcon\Di;
use Phalcon\Forms\Form;
use Phalcon\Validation\Message\Group as MessageGroup;
use PhalconX\Test\TestCase;
use PhalconX\Test\Form\User;
use PhalconX\Test\Models\Pet;
use PhalconX\Exception\ValidationException;

class ValidatorFormTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        Di::getDefault()->setShared('reflection', 'PhalconX\Util\Reflection');
        Di::getDefault()->setShared('objectMapper', 'PhalconX\Util\ObjectMapper');
        Di::getDefault()->setShared('validator', 'PhalconX\Validator');
        $this->validator = new Validator;
    }

    public function testCreateForm()
    {
        $form =$this->validator->createForm(User::CLASS);
        // print_r($form);
        $this->assertTrue($form instanceof Form);
    }
}
