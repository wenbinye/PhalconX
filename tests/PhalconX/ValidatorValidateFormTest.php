<?php
namespace PhalconX;

use Phalcon\Di;
use Phalcon\Validation\Message\Group as MessageGroup;
use PhalconX\Test\TestCase;
use PhalconX\Test\Form\User;
use PhalconX\Test\Models\Pet;
use PhalconX\Exception\ValidationException;

class ValidatorValidateFormTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new Validator;
    }

    /**
     * @dataProvider userDataProvider
     */
    public function testUserForms($formData, $result, $field)
    {
        $form = $this->objectMapper->map($formData, User::CLASS);
        if ($result) {
            $this->validator->validate($form);
            $this->assertTrue(true);
        } else {
            try {
                $this->validator->validate($form);
                $this->fail();
            } catch (ValidationException $e) {
                $errors = $e->getErrors();
                // print_r($errors);
                $this->assertTrue($errors instanceof MessageGroup);
                $this->assertEquals($errors[0]->getField(), $field);
            }
        }
    }

    public function userDataProvider()
    {
        return [
            [['id'=>'1', 'name' => 'john', 'age'=>'10'], true, null],
            [['id'=>'1', 'name' => 'john', 'age'=>'1000'], false, 'age'],
            [['id'=>'1', 'name' => 'john smith william', 'age'=>'10'], false, 'name'],
        ];
    }

    /**
     * @dataProvider petDataProvider
     */
    public function testPetForms($formData, $result, $field)
    {
        $form = $this->objectMapper->map($formData, Pet::CLASS);
        
        if ($result) {
            $this->validator->validate($form);
            $this->assertTrue(true);
        } else {
            try {
                $this->validator->validate($form);
                $this->fail();
            } catch (ValidationException $e) {
                $errors = $e->getErrors();
                // print_r($errors);
                $this->assertTrue($errors instanceof MessageGroup);
                $this->assertEquals($errors[0]->getField(), $field);
            }
        }
    }

    public function petDataProvider()
    {
        return [
            [['id'=>'1', 'name' => 'huahua'], false, 'photo_urls'],
            [['id'=>'1', 'name' => 'huahua', 'photo_urls' => ['a'], 'category'=> ['id' => 'a']], false, 'category.id'],
        ];
    }
}
