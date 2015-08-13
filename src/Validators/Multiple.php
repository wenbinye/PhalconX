<?php
namespace PhalconX\Validators;

class Multiple extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        foreach ($this->getOption('validators') as $val) {
            if (!$val->validate($validator, $attribute)) {
                return false;
            }
        }
        return true;
    }
}
