<?php
namespace PhalconX\Console\Annotations;

use Symfony\Component\Console\Input\InputArgument;

class Argument extends Annotation
{
    protected static $DEFAULT_PROPERTY = 'mode';
    
    public $name;

    public $mode;

    public function getMode()
    {
        if ($this->mode) {
            $mode = 0;
            foreach (explode('|', strtoupper($this->mode)) as $one) {
                $mode |= constant(InputArgument::class . '::' . $one);
            }
            return $mode;
        }
    }
}
