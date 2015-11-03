<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Validation\Validators\Max as MaxValidator;

class Max extends Validator
{
    protected static $validatorClass = MaxValidator::class;

    /**
     * @var int the maximum value
     */
    public $value;

    /**
     * @var boolean whether the checked value can equal to the maximum value
     */
    public $exclusive = false;

    /**
     * @var string error message
     */
    public $message;
}
