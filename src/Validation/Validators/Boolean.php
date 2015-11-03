<?php
namespace PhalconX\Validation\Validators;

class Boolean extends Validator
{
    protected $defaultMessageName = "NotBoolean";
    
    protected function check($value, $validation)
    {
        return in_array($value, [1, 0, 'true', 'false', '1', '0', true, false], true);
    }
}
