<?php
namespace PhalconX\Test\Form;

use PhalconX\Validators\StringLength;
use PhalconX\Validators\Range;

class User
{
    /**
     * @Text(label="User Id")
     */
    public $id;

    /**
     * @Text(label="Name")
     * @Valid(type=string, maxLength=10)
     */
    public $name;

    /**
     * @Text(label="Age")
     * @Valid(type=integer, minimum=0, maximum=200)
     */
    public $age;
}
