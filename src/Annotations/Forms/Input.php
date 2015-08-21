<?php
namespace PhalconX\Annotations\Forms;

use PhalconX\Annotations\Annotation;

class Input extends Annotation
{
    protected static $elementClass = "PhalconX\Forms\Element\Input";
    
    public $name;

    public $label;

    protected $attributes;

    public function __construct($args = null)
    {
        if (!$args) {
            return;
        }
        foreach ($args as $name => $val) {
            if (property_exists($this, $name)) {
                $this->$name = $val;
            } else {
                $this->attributes[$name] = $val;
            }
        }
    }
    
    public function process()
    {
        $elem = new static::$elementClass($this->name, $this->attributes);
        $elem->setLabel($this->getLabel());
        return $elem;
    }

    public function getLabel()
    {
        return $this->label ? $this->label
            : str_replace('_', ' ', ucfirst($this->name));
    }
}
