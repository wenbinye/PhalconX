<?php
namespace PhalconX\Console\Annotations;

class Command extends Annotation
{
    protected static $DEFAULT_PROPERTY = 'name';
    
    public $name;

    public $aliases;

    public $title;
}
