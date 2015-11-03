<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\Email as EmailValidator;

class Email extends Validator
{
    protected static $validatorClass = EmailValidator::class;

    /**
     * @var string error message
     */
    public $message;
}
