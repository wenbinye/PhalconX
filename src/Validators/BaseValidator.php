<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

abstract class BaseValidator extends Validator implements ValidatorInterface
{
    protected function getOptionDef($option, $default=null)
    {
        $value = $this->getOption($option);
        return isset($value) ? $value : $default;
    }

    protected function getMessage($default=null)
    {
        return $this->getOptionDef('message', $default);
    }
}
