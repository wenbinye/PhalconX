<?php
namespace PhalconX\Annotations\Forms;

use PhalconX\Annotations\Annotation;

class Input extends Annotation
{
    protected static $elementClass = "PhalconX\Forms\Element\Input";
    
    public $name;

    public $label;

    protected $attributes;

    public function __construct($args)
    {
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
        if ($this->label) {
            $elem->setLabel($this->label);
        } else {
            $elem->setLabel(ucfirst($this->name));
        }
        return $elem;
    }
}
