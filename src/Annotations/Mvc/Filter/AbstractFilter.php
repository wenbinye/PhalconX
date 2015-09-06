<?php
namespace PhalconX\Annotations\Mvc\Filter;

use PhalconX\Di\Injectable;
use PhalconX\Annotations\Annotation;

abstract class AbstractFilter extends Annotation
{
    use Injectable;

    public $priority = 1024;
    
    abstract public function filter();
}
