<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\Confirmation as ConfirmationValidator;

class Confirmation extends Validator
{
    protected static $DEFAULT_PROPERTY = 'with';

    protected static $validatorClass = ConfirmationValidator::class;

    /**
     * @var string the field should identical to
     */
    public $with;

    /**
     * @var string error message
     */
    public $message;
}
