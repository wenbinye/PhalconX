<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Validation\Validators\Datetime as DatetimeValidator;

class Datetime extends Validator
{
    protected static $validatorClass = DatetimeValidator::class;

    protected static $DEFAULT_PROPERTY = 'pattern';

    /**
     * @var string datetime pattern
     */
    public $pattern = 'Y-m-d H:i:s';

    /**
     * @var string error message
     */
    public $message;
}
