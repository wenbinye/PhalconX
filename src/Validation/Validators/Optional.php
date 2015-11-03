<?php
namespace PhalconX\Validation\Validators;

class Optional extends Validator
{
    public function check($value, $validation)
    {
        $other = $validation->getValue($this->getOption('with'));
        return $this->hasValue($value) || $this->hasValue($other);
    }

    private function hasValue($value)
    {
        return isset($value) && $value !== '';
    }
}
