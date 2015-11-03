<?php
namespace PhalconX\Test\Validation\Annotations;

use Phalcon\Validation;
use PhalconX\Annotation\Context;
use PhalconX\Test\TestCase as BaseTestCase;
use PhalconX\Validation\Form;

abstract class TestCase extends BaseTestCase
{
    protected $form;
    protected $validation;
    protected $annotation;
    
    public function setUp()
    {
        $this->form = new Form();
        $this->validation = new Validation();
    }

    protected function getAnnotation($args = [])
    {
        $context = new Context([
            'class' => get_class($this),
            'declaringClass' => get_class($this),
            'type' => Context::TYPE_PROPERTY,
            'name' => 'value',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
        return new static::$annotationClass($args, $context);
    }
}
