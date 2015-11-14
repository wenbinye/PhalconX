<?php
namespace PhalconX\Console\Annotations;

use Symfony\Component\Console\Input\InputOption;

class Option extends Annotation
{
    protected static $DEFAULT_PROPERTY = 'mode';

    public $name;

    public $shortcut;

    public $mode;

    public function getMode()
    {
        if ($this->mode) {
            $mode = 0;
            foreach (explode('|', strtoupper($this->mode)) as $one) {
                $mode |= constant(InputOption::class . '::' . 'VALUE_' . $one);
            }
            return $mode;
        }
    }
}
