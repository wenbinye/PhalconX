<?php
namespace PhalconX\Console\Annotations;

use Symfony\Component\Console\Input\InputOption;

class Option extends Annotation
{
    public $name;

    public $shortcut;

    public $model;

    public function getMode()
    {
        if ($this->model) {
            return constant(InputOption::class . '::' . 'VALUE_' . strtoupper($this->model));
        }
    }
}
