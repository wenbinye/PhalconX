<?php
namespace PhalconX\Test\Annotation;

use PhalconX\Test\Annotation\Validators\Max;
use PhalconX\Test\Annotation\Validators\Range;
use PhalconX\Test\Annotation\Validators\Match;

/**
 * @Match(pattern="class")
 */
class FormFilter
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

    /**
     * @Match(pattern=foo)
     */
    public function foo()
    {
    }
}
