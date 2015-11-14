<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\Identical as IdenticalValidator;

class Identical extends Validator
{
    protected static $validatorClass = IdenticalValidator::class;

    protected static $DEFAULT_PROPERTY = 'accepted';

    /**
     * @var string the value should be identical
     */
    public $accepted;

    /**
     * @var string error message
     */
    public $message;
}
