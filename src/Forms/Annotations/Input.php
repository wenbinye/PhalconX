<?php
namespace PhalconX\Forms\Annotations;

use PhalconX\Annotation\Annotation;
use PhalconX\Forms\Element\Input as InputElement;
use PhalconX\Validation\Validation;

/**
 * Annotation class for form element
 */
class Input extends Annotation implements InputInterface
{
    /**
     * @var string the input type class
     */
    protected static $elementClass = InputElement::class;

    /**
     * @var string input name
     */
    public $name;

    /**
     * @var string input label
     */
    public $label;

    /**
     * @var string|array filters
     */
    public $filters;
    
    /**
     * @var additional attributes
     */
    protected $_attributes;

    protected function assign($args)
    {
        foreach ($args as $name => $val) {
            if (property_exists($this, $name)) {
                $this->$name = $val;
            } else {
                $this->_attributes[$name] = $val;
            }
        }
    }

    /**
     * Gets the form element
     *
     * @return InputElement
     */
    public function getElement(Validation $form)
    {
        return new static::$elementClass($this->name, $this->getAttributes());
    }

    /**
     * Gets the additional attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }
}
