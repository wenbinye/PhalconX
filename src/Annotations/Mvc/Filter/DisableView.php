<?php
namespace PhalconX\Annotations\Mvc\Filter;

use Phalcon\Mvc\View;

class DisableView extends AbstractFilter
{
    public $value;
    
    public function filter()
    {
        if (empty($this->value)) {
            $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
        } else {
            if (!is_array($this->value)) {
                $this->value = [$this->value];
            }
            $disabled = [];
            foreach ($this->value as $level) {
                $const = View::CLASS.'::LEVEL_' . strtoupper($level);
                if (defined($const)) {
                    $disabled[constant($const)] = true;
                }
            }
            $this->view->disableLevel($disabled);
        }
    }
}
