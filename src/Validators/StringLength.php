<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;

class StringLength extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $encoding = $this->getOptionWithDefault('encoding', 'utf8');
        $strlen = mb_strlen($validator->getValue($attribute), $encoding);
        if ($strlen > $this->getOptionWithDefault('max', PHP_INT_MAX)) {
            $msg = $this->getOption('messageMaximum', "Value for $attribute is too long");
        } elseif ($strlen < $this->getOptionWithDefault('min', 0)) {
            $msg = $this->getOption('messageMinimum', "Value for $attribute is too short");
        }
        if (isset($msg)) {
            $validator->appendMessage(new Message($msg, $attribute));
            return false;
        }
        return true;
    }
}
