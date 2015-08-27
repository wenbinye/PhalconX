<?php
namespace PhalconX\Annotations\Mvc\Router;

use PhalconX\Annotations\Annotation;

/**
 * TODO support priority
 */
class Route extends Annotation
{
    public $value;

    public $paths = [];

    public $methods;

    public $conversors;

    public $converts;

    public $beforeMatch;

    public $name;
}
