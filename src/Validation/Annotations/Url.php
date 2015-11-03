<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\Url as UrlValidator;

class Url extends Validator
{
    protected static $validatorClass = UrlValidator::class;

    /**
     * @var string error message
     */
    public $message;
}
