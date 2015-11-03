<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Validation\Validators\Min as MinValidator;

class Min extends Validator
{
    protected static $validatorClass = MinValidator::class;

    /**
     * @var int the minimum value
     */
    public $value;

    /**
     * @var boolean whether the checked value can equal to the minimum value
     */
    public $exclusive = false;

    /**
     * @var string error message
     */
    public $message;
}
