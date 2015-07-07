<?php
namespace PhalconX\Test\Form;

use PhalconX\Validators\StringLength;
use PhalconX\Validators\Range;

class User
{
    public $id;

    /**
     * @Valid(validator=@StringLength(max=10))
     */
    public $name;

    /**
     * @Valid(type=integer, validator=@Range(minimum=0, maximum=200))
     */
    public $age;
}
