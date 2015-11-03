<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Validation\Validators\Boolean as BooleanValidator;

class Boolean extends Validator
{
    protected static $validatorClass = BooleanValidator::class;

    /**
     * @var string error message
     */
    public $message;
}
