<?php
namespace PhalconX\Annotations\Forms;

use Phalcon\Forms\Element\Check as CheckElement;
use PhalconX\Exception;
use Phalcon\Mvc\Model;

class Check extends Select
{
    public function process()
    {
        $this->initialize();
        if ($this->options instanceof ResultSet) {
            $options = [];
            $zero = $this->using[0];
            $one = $this->using[1];
            foreach ($this->options as $model) {
                $options[$model->readAttribute($zero)] = $model->readAttribute($one);
            }
            $this->options =
        }
        $elem = new CheckElement($this->name, $this->options, $this->attributes);
    }
}
