<?php
namespace PhalconX\Mvc\View;

use Phalcon\Text;

class VoltExtension
{
    private $viewHelper;

    public function __construct($viewHelper)
    {
        $this->viewHelper = $viewHelper;
    }
    
    public function compileFunction($name, $arguments)
    {
        $method = Text::camelize($name);
        if (method_exists($this->viewHelper, 'compile' . $method)) {
            return call_user_func([$this->viewHelper, 'compile'.$method], $arguments);
        } elseif (method_exists($this->viewHelper, $method)) {
            return '$this->viewHelper->'.$method . '(' . $arguments . ')';
        }
    }

    public function getViewHelper()
    {
        return $this->viewHelper;
    }
}
