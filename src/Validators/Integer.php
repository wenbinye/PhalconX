<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;
use PhalconX\Messages;

class Integer extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $value = $validator->getValue($attribute);
        if (is_integer($value)) {
            return true;
        } else {
            $message = $this->getMessage(
                Messages::format('The :attribute must be an integer', $attribute)
            );
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        }
    }
}
