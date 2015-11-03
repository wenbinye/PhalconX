<?php
namespace PhalconX\Validation\Validators;

class Min extends Validator
{
    protected $defaultMessageName = "TooSmall";

    protected function getMessageVars()
    {
        return [':value' => $this->getOption('value')];
    }
    
    protected function check($value, $validation)
    {
        if ($this->getOption('exclusive')) {
            return $value > $this->getOption('value');
        } else {
            return $value >= $this->getOption('value');
        }
    }
}
