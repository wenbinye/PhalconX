<?php
namespace PhalconX\Test\Validation;

use PhalconX\Forms\Annotations\Label;
use PhalconX\Forms\Annotations\Text;
use PhalconX\Validation\Annotations\StringLength;
use PhalconX\Validation\Annotations\Range;
use PhalconX\Validation\Annotations\Required;
use PhalconX\Validation\Annotations\Integer;

class User
{
    /**
     * @Label("User Id")
     * @Text
     * @Integer
     */
    public $id;

    /**
     * @Text
     * @Required
     * @StringLength(max=10)
     */
    public $name;

    /**
     * @Text
     * @Range(min=0, max=200)
     */
    public $age = 1;

    public $email;

    public function __toString()
    {
        return json_encode(get_object_vars($this));
    }
}
