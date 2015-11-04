<?php
namespace PhalconX\Console\Annotations;

use PhalconX\Annotation\Annotation as BaseAnnotation;

class Annotation extends BaseAnnotation
{
    public $desc;

    public $description;

    public function getDescription()
    {
        return $this->desc ?: $this->description;
    }
}
