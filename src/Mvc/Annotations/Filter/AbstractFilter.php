<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Di\Injectable;
use PhalconX\Annotation\Annotation;

abstract class AbstractFilter extends Annotation implements FilterInterface
{
    use Injectable;
    
    public $priority = 1024;
}
