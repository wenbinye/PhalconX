<?php
namespace PhalconX\Mvc\Controller\Filter;

use Phalcon\Di\Injectable;
use Phalcon\Mvc\View;

class DisableView extends Injectable implements FilterInterface
{
    private $levels;
    
    public function __construct($levels = null)
    {
        $this->levels = $levels;
    }
    
    public function filter($dispatcher)
    {
        if (empty($this->levels)) {
            $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
        } else {
            $disabled = [];
            foreach ($this->levels as $level) {
                $const = View::CLASS.'::LEVEL_' . strtoupper($level);
                if (defined($const)) {
                    $disabled[constant($const)] = true;
                }
            }
            $this->view->disableLevel($disabled);
        }
    }
}
