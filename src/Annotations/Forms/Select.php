<?php
namespace PhalconX\Annotations\Forms;

use Phalcon\Forms\Element\Select as SelectElement;
use PhalconX\Exception;
use Phalcon\Mvc\Model;
use PhalconX\Enums\Enum;

class Select extends Input
{
    protected static $elementClass = SelectElement::CLASS;

    public $enum;

    public $criteria;

    public $using;

    public $options;

    public function process()
    {
        $this->initialize();
        $this->attributes['using'] = $this->using;
        $elem = new static::$elementClass($this->name, $this->options, $this->attributes);
        $elem->setLabel($this->getLabel());
        return $elem;
    }

    public function initialize()
    {
        if (!$this->enum) {
            return;
        }
        $clz = $this->resolveImport($this->enum);
        if (is_subclass_of($clz, Enum::CLASS)) {
            $options = [];
            foreach ($clz::all() as $enum) {
                $options[$enum->value] = $enum->description;
            }
            $this->options = $options;
        } elseif (is_subclass_of($clz, Model::CLASS)) {
            if (empty($this->using)) {
                throw new Exception("The 'using' parameter is required for " . $this);
            }
            $this->criteria['columns'] = $this->using;
            $this->options = call_user_func(array($clz, 'find'), $this->criteria);
        } else {
            throw new Exception(
                "Wrong enum class '$clz' for Select Element in " . $this,
                Exception::ERROR_INVALID_ARGUMENT
            );
        }
    }
}
