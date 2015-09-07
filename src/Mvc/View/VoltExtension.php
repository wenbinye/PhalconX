<?php
namespace PhalconX\Mvc\View;

use Phalcon\Text;
use PhalconX\Util;

class VoltExtension
{
    private $viewHelper;
    
    public function compileFunction($name, $arguments)
    {
        $cname = Text::camelize($name);
        if (method_exists($this->getViewHelper(), $cname)) {
            return '$this->viewHelper->'.$cname . '(' . $arguments . ')';
        }
    }

    public function getViewHelper()
    {
        if (!$this->viewHelper) {
            $this->viewHelper = Util::service('viewHelper');
        }
        return $this->viewHelper;
    }

    public function setViewHelper($viewHelper)
    {
        $this->viewHelper = $viewHelper;
        return $this;
    }
}
