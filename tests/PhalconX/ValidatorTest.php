<?php
namespace PhalconX;

use PhalconX\Test\TestCase;
use PhalconX\Test\Form\User;

use Phalcon\Validation\Message\Group as MessageGroup;

class ValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new Validator;
    }

    /**
     * @dataProvider dataProvider
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

    public function dataProvider()
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
            [['name' => 'ids', 'value' => array('10'), 'type' => 'int_array'], true],
        ];
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testForms($formData, $result, $field)
    {
        $form = new User();
        foreach ($formData as $k => $v) {
            $form->$k = $v;
        }
        if ($result) {
            $this->validator->validate($form);
            $this->assertTrue(true);
        } else {
            try {
                $this->validator->validate($form);
                $this->fail();
            } catch (ValidationException $e) {
                $errors = $e->getErrors();
                $this->assertTrue($errors instanceof MessageGroup);
                $this->assertEquals($errors[0]->getField(), $field);
            }
        }
    }

    public function formDataProvider()
    {
        return [
            [['id'=>'1', 'name' => 'john', 'age'=>'10'], true, null],
            [['id'=>'1', 'name' => 'john', 'age'=>'1000'], false, 'age'],
            [['id'=>'1', 'name' => 'john smith william', 'age'=>'10'], false, 'name'],
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
                'name' => 'ids', 'value' => 'abc', 'type' => 'int_array'
            ]]);
            $this->fail();
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertTrue($errors instanceof MessageGroup);
            $this->assertEquals($errors[0]->getField(), 'ids');
        }
    }
}
