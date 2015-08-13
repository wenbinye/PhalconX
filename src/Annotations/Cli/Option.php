<?php
namespace PhalconX\Annotations\Cli;

use Phalcon\Text;

class Option extends Argument
{
    public $short;

    public $long;

    public $optional;

    public function __construct($args)
    {
        foreach ([0, 1] as $i) {
            if (!empty($args[$i]) && $args[$i][0] == '-' && strlen($args[$i]) >= 2) {
                if (!$this->long && Text::startsWith($args[$i], '--')) {
                    $this->long = substr($args[$i], 2);
                } elseif (!$this->short && strlen($args[$i]) == 2 && ctype_alpha($args[$i][1])) {
                    $this->short = substr($args[$i], 1);
                }
                unset($args[$i]);
            }
        }
        parent::__construct($args);
        if (!isset($this->optional)) {
            $this->optional = $this->type == 'boolean';
        }
    }
}
