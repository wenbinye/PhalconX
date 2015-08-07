<?php
namespace PhalconX\Test\Form;

use PhalconX\Enums\Boolean;

class Query
{
    /**
     * @Valid(required=true, type=string, enum=Boolean)
     */
    public $flag;
}
