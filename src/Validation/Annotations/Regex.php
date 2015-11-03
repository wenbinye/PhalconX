<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\Regex as RegexValidator;

class Regex extends Validator
{
    protected static $DEFAULT_PROPERTY = 'pattern';
    
    protected static $validatorClass = RegexValidator::class;

    /**
     * @var string match pattern
     */
    public $pattern;

    /**
     * @var string error message
     */
    public $message;
}
