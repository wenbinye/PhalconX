<?php
namespace PhalconX\View;

class VoltExtension
{
    public function compileFunction($name, $arguments)
    {
        if ( is_callable('\PhalconX\Html::' . $name) ) {
            return '\PhalconX\Html::'.$name . '(' . $arguments . ')';
        }
    }
}
