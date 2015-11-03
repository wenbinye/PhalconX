<?php
namespace PhalconX\Validation\Validators;

class Max extends Validator
{
    protected $defaultMessageName = "TooLarge";

    protected function getMessageVars()
    {
        return [':value' => $this->getOption('value')];
    }
    
    protected function check($value, $validation)
    {
        if ($this->getOption('exclusive')) {
            return $value < $this->getOption('value');
        } else {
            return $value <= $this->getOption('value');
        }
    }
}
