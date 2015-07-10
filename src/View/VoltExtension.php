<?php
namespace PhalconX\View;

class VoltExtension
{
    public function compileFunction($name, $arguments)
    {
        $cname = camelize($name);
        if (is_callable('\PhalconX\Html::' . $cname)) {
            return '\PhalconX\Html::'.$cname . '(' . $arguments . ')';
        }
    }
    
    public static function camelize($word)
    {
        return preg_replace('/(^|_)([a-z])/e', 'strtoupper("\\2")', $word);
    }
}
