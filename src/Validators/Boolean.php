<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;
use PhalconX\Messages;

class Boolean extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $value = $validator->getValue($attribute);
        if (in_array($value, [1, 0, 'true', 'false', '1', '0'], true)) {
            return true;
        } else {
            $message = $this->getMessage(
                Messages::format('The :attribute must be a boolean value, eg. true, false', $attribute)
            );
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        }
    }
}
