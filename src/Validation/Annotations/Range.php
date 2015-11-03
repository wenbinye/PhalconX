<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Validation\Validators\Range as RangeValidator;

class Range extends Validator
{
    protected static $validatorClass = RangeValidator::class;

    /**
     * @var int the maximum value
     */
    public $max;

    /**
     * @var int the minimum value
     */
    public $min;

    public $exclusiveMaximum = false;

    public $exclusiveMinimum = false;

    /**
     * @var string error message
     */
    public $messageMaximum;

    /**
     * @var string error message
     */
    public $messageMinimum;
}
