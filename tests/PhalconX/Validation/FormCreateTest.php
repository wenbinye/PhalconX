<?php
namespace PhalconX\Validation;

use PhalconX\Test\TestCase;
use PhalconX\Test\Validation\User;
use PhalconX\Forms\Form as PhalconForm;
use PhalconX\Helper\ArrayHelper;

/**
 * TestCase for Form
 */
class FormCreateTest extends TestCase
{
    private $form;

    public function setUp()
    {
        $this->form = new Form;
    }

    public function testIsForm()
    {
        $form = $this->form->create(User::class);
        $this->assertTrue($form instanceof PhalconForm);
    }
    
    public function testAssertFieldsMatch()
    {
        $form = $this->form->create(User::class);
        $this->assertEquals(ArrayHelper::pull($form->getElements(), 'name', ArrayHelper::GETTER),
                            ['id', 'name', 'age']);
    }

    public function testLabelMatch()
    {
        $form = $this->form->create(User::class);
        $this->assertEquals($form->get('id')->getLabel(), 'User Id');
        $this->assertEquals($form->get('name')->getLabel(), 'Name');
    }

    public function testDefaultValue()
    {
        $form = $this->form->create(User::class);
        $this->assertEquals($form->get('age')->getValue(), 1);
    }

    public function testRequiredIfMissingValue()
    {
        $form = $this->form->create(User::class);
        // print_r($form);
        $this->assertTrue(empty($form->get('id')->getValidators()), 'not required');
        $this->assertTrue(!empty($form->get('name')->getValidators()), 'required');
    }

    public function testRequiredIfHasValue()
    {
        $model = new User;
        $model->id = 'abc';
        $form = $this->form->create($model);
        $this->assertTrue(!empty($form->get('id')->getValidators()), 'not required but has value');
        $this->assertTrue(!empty($form->get('name')->getValidators()), 'required');
    }
}
