<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\Numericality;

class Number extends Validator
{
    protected static $validatorClass = Numericality::class;

    /**
     * @var string error message
     */
    public $message;
}
