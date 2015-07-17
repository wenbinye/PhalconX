<?php
namespace PhalconX;

use Phalcon\Di;
use Phalcon\Validation\Message\Group as MessageGroup;
use PhalconX\Test\TestCase;
use PhalconX\Test\Form\User;
use PhalconX\Test\Models\Pet;
use PhalconX\Exception\ValidationException;

class ValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        Di::getDefault()->setShared('reflection', 'PhalconX\Util\Reflection');
        Di::getDefault()->setShared('objectMapper', 'PhalconX\Util\ObjectMapper');
        Di::getDefault()->setShared('validator', 'PhalconX\Validator');
        $this->validator = new Validator;
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testValidators($validator, $result)
    {
        if ($result) {
            $this->validator->validate([$validator]);
            $this->assertTrue(true);
        } else {
            try {
                $this->validator->validate([$validator]);
                $this->fail();
            } catch (ValidationException $e) {
                $errors = $e->getErrors();
                $this->assertTrue($errors instanceof MessageGroup);
                $this->assertEquals($errors[0]->getField(), $validator['name']);
            }
        }
    }

    public function formDataProvider()
    {
        return [
            [['name' => 'id', 'value' => '10', 'type' => 'integer'], true],
            [['name' => 'id', 'required' => true, 'type' => 'integer'], false],
            [['name' => 'id', 'value' => 'abc', 'type' => 'integer'], false],
            [['name' => 'id', 'value' => '1', 'type' => 'boolean'], true],
            [['name' => 'id', 'value' => '0', 'type' => 'boolean'], true],
            [['name' => 'id', 'value' => 'true', 'type' => 'boolean'], true],
            [['name' => 'id', 'value' => 'false', 'type' => 'boolean'], true],
            [['name' => 'id', 'value' => 'abc', 'type' => 'boolean'], false],
            [['name' => 'ids', 'value' => array('10'), 'type' => 'array', 'elementType' => 'integer'], true],
        ];
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

    public function testDefaultValue()
    {
        $id = '';
        $this->validator->validate([
            ['name'=>'id', 'default' => 10, 'value' => &$id]
        ]);
        $this->assertEquals($id, 10);
    }
    
    public function testIntArrayFail()
    {
        try {
            $this->validator->validate([[
                'name' => 'ids', 'value' => 'abc', 'type' => 'array', 'element' => 'integer'
            ]]);
            $this->fail();
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertTrue($errors instanceof MessageGroup);
            $this->assertEquals($errors[0]->getField(), 'ids');
        }
    }
    
    public function testIntArrayElementFail()
    {
        try {
            $this->validator->validate([[
                'name' => 'ids', 'value' => ['abc'], 'type' => 'array', 'element' => 'integer'
            ]]);
            $this->fail();
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertTrue($errors instanceof MessageGroup);
            $this->assertEquals($errors[0]->getField(), 'ids[0]');
        }
    }
}
