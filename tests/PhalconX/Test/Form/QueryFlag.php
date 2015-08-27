<?php
namespace PhalconX\Test\Form;

use PhalconX\Enums\Boolean;

class QueryFlag
{
    /**
     * @Valid(required=true, type=integer, enum='Boolean.values')
     */
    public $flag;
}
