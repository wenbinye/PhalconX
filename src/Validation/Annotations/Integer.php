<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\Regex;
use PhalconX\Annotation\Annotation;
use PhalconX\Validation\Validation;

class Integer extends Annotation implements ValidatorInterface
{
    public $message;
    
    public function getValidator(Validation $validation)
    {
        return new Regex([
            'pattern' => '/^-?\d+$/',
            'message' => $this->message
        ]);
    }
}
