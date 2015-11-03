<?php
namespace PhalconX\Test\Validation\Annotations;

use Phalcon\Validation;
use PhalconX\Annotation\Context;
use PhalconX\Test\TestCase as BaseTestCase;
use PhalconX\Validation\Form;
use PhalconX\Test\Helper;

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
        $context = Helper::createAnnotationContext($this, 'property', 'value');
        return new static::$annotationClass($args, $context);
    }
}
