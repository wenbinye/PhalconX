<?php
namespace PhalconX\Console\Annotations;

use Symfony\Component\Console\Input\InputArgument;

class Argument extends Annotation
{
    public $name;

    public $mode;

    public function getMode()
    {
        if ($this->mode) {
            return constant(InputArgument::class . '::' . strtoupper($this->model));
        }
    }
}
