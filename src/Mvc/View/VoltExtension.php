<?php
namespace PhalconX\Mvc\View;

use Phalcon\Text;
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
        $cname = Text::camelize($name);
        if (method_exists($this->voltFunctions, $cname)) {
            return '$this->voltFunctions->'.$cname . '(' . $arguments . ')';
        }
    }
}
