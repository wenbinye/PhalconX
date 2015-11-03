<?php
namespace PhalconX\Test\Annotation;

class Form
{
    /**
     * @Max(10)
     */
    public $size;

    /**
     * @Range(min=1, max=100)
     */
    public $age;

    /**
     * @Match(pattern="/^[A-Z][0-9a-z]+$/")
     */
    public $name;
}
