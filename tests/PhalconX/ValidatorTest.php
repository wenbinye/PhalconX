<?php
namespace PhalconX;

use Phalcon\Di;
use Phalcon\Validation\Message\Group as MessageGroup;
use PhalconX\Test\TestCase;
use PhalconX\Annotations\Validator\Valid;
use PhalconX\Exception\ValidationException;

class ValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new Validator;
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testValidators($validator, $value, $result)
    {
        $data = [$validator['name'] => $value];
        if ($result) {
            $this->validator->validate($data,[new Valid($validator)]);
            $this->assertTrue(true);
        } else {
            try {
                $this->validator->validate($data,[new Valid($validator)]);
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
            [['name' => 'id', 'type' => 'integer'], '10', true],
            [['name' => 'id', 'required' => true, 'type' => 'integer'], null, false],
            [['name' => 'id', 'type' => 'integer'], 'abc', false],
            [['name' => 'id', 'type' => 'boolean'], '1', true],
            [['name' => 'id', 'type' => 'boolean'], '0', true],
            [['name' => 'id', 'type' => 'boolean'], 'true', true],
            [['name' => 'id', 'type' => 'boolean'], 'false', true],
            [['name' => 'id', 'type' => 'boolean'], 'abc', false],
            [['name' => 'ids','type' => 'array', 'element' => ['type' => 'integer']], 'value' => array('10'), true],
        ];
    }

    public function testDefaultValue()
    {
        $data = ['id' => ''];
        $this->validator->validate($data, [
            new Valid(['name'=>'id', 'type' => 'integer', 'default' => 10])
        ]);
        $this->assertEquals($data['id'], 10);
    }
    
    public function testIntArrayFail()
    {
        try {
            $data = ['ids' => 'abc'];
            $this->validator->validate($data, [
                new Valid(['name' => 'ids', 'type' => 'array', 'element' => 'integer'])
            ]);
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
            $data = ['ids' => ['abc']];
            $this->validator->validate($data, [
                new Valid(['name' => 'ids', 'type' => 'array', 'element' => 'integer'])
            ]);
            $this->fail();
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertTrue($errors instanceof MessageGroup);
            $this->assertEquals($errors[0]->getField(), 'ids[0]');
        }
    }
}
