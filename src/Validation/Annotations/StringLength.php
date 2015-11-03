<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\StringLength as StringLengthValidator;

class StringLength extends Validator
{
    protected static $validatorClass = StringLengthValidator::class;

    /**
     * @var int
     */
    public $max;

    /**
     * @var int
     */
    public $min;

    public $messageMaximum;

    public $messageMinimum;
}
