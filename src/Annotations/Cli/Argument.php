<?php
namespace PhalconX\Annotations\Cli;

use PhalconX\Annotations\Annotation;

class Argument extends Annotation
{
    protected static $DEFAULT_PROPERTY = 'name';
    
    public $name;

    public $type;

    public $help;

    public $required;

    public $value;

    public $default;
}
