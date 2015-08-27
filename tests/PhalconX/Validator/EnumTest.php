<?php
namespace PhalconX\Validator;

use PhalconX\Exception\ValidationException;
use Phalcon\Validation\Message\Group as MessageGroup;
use PhalconX\Test\Form\Query;
use PhalconX\Test\Form\QueryFlag;
use PhalconX\Test\Form\Query\SubQuery;
use PhalconX\Test\TestCase;

class EnumTest extends TestCase
{
    /**
     * @dataProvider formDataProvider
     */
    public function testQuery($formData, $result, $field)
    {
        $form = $this->objectMapper->map($formData, Query::CLASS);
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

    /**
     * @dataProvider formDataProvider
     */
    public function testSubclass($formData, $result, $field)
    {
        $form = $this->objectMapper->map($formData, SubQuery::CLASS);
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

    /**
     * @dataProvider valuesProvider
     */
    public function testEnumValues($formData, $result, $field)
    {
        $form = $this->objectMapper->map($formData, QueryFlag::CLASS);
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

    public function formDataProvider()
    {
        return [
            [['flag' => 'true'], true, null],
            [['flag' => '1'], false, 'flag'],
        ];
    }

    public function valuesProvider()
    {
        return [
            [['flag' => 'true'], false, 'flag'],
            [['flag' => '1'], true, null],
        ];
    }
}
