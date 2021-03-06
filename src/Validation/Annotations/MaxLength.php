<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\StringLength as StringLengthValidator;
use PhalconX\Validation\Validation;

class MaxLength extends Validator
{
    protected static $validatorClass = StringLengthValidator::class;

    /**
     * @var int
     */
    public $value;

    public $message;

    public function getValidator(Validation $validation)
    {
        return new static::$validatorClass([
            'max' => $this->value,
            'messageMaximum' => $this->message
        ]);
    }
}
