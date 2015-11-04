<?php
namespace PhalconX\Mvc\Annotations\Route;

use PhalconX\Annotation\Annotation;

/**
 * TODO support priority
 */
class Route extends Annotation
{
    /**
     * @var string Path pattern
     */
    public $value;

    /**
     * @var array overwrite module, namespace, controller, action
     */
    public $paths = [];

    /**
     * @var array request methods
     */
    public $methods;

    /**
     * @var array convert callback
     */
    public $converters;

    /**
     * @var closure before match callback
     */
    public $beforeMatch;

    /**
     * @var route name
     */
    public $name;
}
