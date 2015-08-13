<?php
namespace PhalconX\Annotations\Validator;

use PhalconX\Validators\IsArray;
use PhalconX\Validators\IsA as IsAValidator;

class IsA extends Validator
{
    public $value;

    protected function getValidators()
    {
        if ($this->isArray()) {
            return [new IsArray([
                'element' => new IsAValidator(['class' => $this->getType()])
            ])];
        } else {
            return [new IsAValidator(['class' => $this->getType()])];
        }
    }

    public function isArray()
    {
        return strpos($this->value, '[]') !== false;
    }

    public function getType()
    {
        return $this->resolveImport(rtrim($this->value, '[]'));
    }
}
