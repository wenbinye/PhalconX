<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Annotation\Annotation;
use PhalconX\Validation\Form;

abstract class Validator extends Annotation implements ValidatorInterface
{
    protected static $validatorClass;
    
    public function getValidator(Form $form)
    {
        return new static::$validatorClass(get_object_vars($this));
    }
}
