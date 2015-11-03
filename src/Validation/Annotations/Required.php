<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\PresenceOf;

class Required extends Validator
{
    protected static $validatorClass = PresenceOf::class;

    /**
     * @var string error message
     */
    public $message;
}
