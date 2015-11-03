<?php
namespace PhalconX\Test\Annotation\Validators;

use PhalconX\Annotation\Annotation;

class Max extends Annotation
{
    protected static $DEFAULT_PROPERTY = 'max';

    public $max;
}
