<?php
namespace PhalconX\Annotation;

class FormImportAlias
{
    /**
     * @Max(10)
     */
    public $size;

    /**
     * @Between(min=1, max=100)
     */
    public $age;

    /**
     * @Match(pattern="/^[A-Z][0-9a-z]+$/")
     */
    public $name;
}
