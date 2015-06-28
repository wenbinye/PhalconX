<?php
namespace PhalconX\Test\Form;

class User
{
    public $id;

    /**
     * @Valid(validator=@StringLength(max=10))
     */
    public $name;

    /**
     * @Valid(type=integer, validator=@Between(minimum=0, maximum=200))
     */
    public $age;
}
