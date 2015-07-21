<?php
namespace PhalconX\Mvc\View;

use Phalcon\Text;
use PhalconX\Util;

class VoltExtension
{
    private $viewHelper;
    
    public function __construct()
    {
        $this->viewHelper = Util::service('viewHelper');
    }
    
    public function compileFunction($name, $arguments)
    {
        $cname = Text::camelize($name);
        if (method_exists($this->viewHelper, $cname)) {
            return '$this->viewHelper->'.$cname . '(' . $arguments . ')';
        }
    }
}
