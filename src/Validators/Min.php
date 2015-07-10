<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;
use PhalconX\Messages;

class Min extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $value = $validator->getValue($attribute);
        $compare = $this->getOption('value');
        if ($value < $compare) {
            $message = $this->getMessage(
                Messages::format('The :attribute must greater than :value', $attribute, $value)
            );
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        } else {
            return true;
        }
    }
}
