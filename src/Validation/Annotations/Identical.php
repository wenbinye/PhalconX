<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\Identical as IdenticalValidator;

class Identical extends Validator
{
    protected static $validatorClass = IdenticalValidator::class;

    /**
     * @var string the value should be identical
     */
    public $value;

    /**
     * @var string error message
     */
    public $message;
}
