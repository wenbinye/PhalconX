<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;

class MbstringLength extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $encoding = $this->getOptionDef('encoding', 'utf8');
        $strlen = mb_strlen($validator->getValue($attribute), $encoding);
        if ($strlen > $this->getOptionDef('max', PHP_INT_MAX)) {
            $msg = $this->getOption('messageMaximum', "Value for $attribute is too long");
        } elseif ($strlen < $this->getOptionDef('min', 0)) {
            $msg = $this->getOption('messageMinimum', "Value for $attribute is too short");
        }
        if (isset($msg)) {
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        }
        return true;
    }
}
