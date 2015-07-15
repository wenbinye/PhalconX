<?php
namespace PhalconX\Mvc\View;

use PhalconX\Util;

class VoltExtension
{
    private $voltFunctions;
    
    public function __construct()
    {
        $this->voltFunctions = Util::service('voltFunctions');
    }
    
    public function compileFunction($name, $arguments)
    {
        $cname = self::camelize($name);
        if (method_exists($this->voltFunctions, $cname)) {
            return '$this->voltFunctions->'.$cname . '(' . $arguments . ')';
        }
    }
    
    public static function camelize($word)
    {
        $parts = explode('_', $word);
        $first = array_shift($parts);
        return $first . implode('', array_map('ucfirst', $parts));
    }
}
