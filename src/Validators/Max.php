<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;
use PhalconX\Messages;

class Max extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $value = $validator->getValue($attribute);
        $compare = $this->getOption('value');
        if ($value > $compare) {
            $message = $this->getMessage(
                Messages::format('The :attribute must less than :value', $attribute, $compare)
            );
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        } else {
            return true;
        }
    }
}
