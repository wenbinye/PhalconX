<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;
use PhalconX\Messages;

class Datetime extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $pattern = $this->getOptionWithDefault('pattern', 'Y-m-d H:i:s');
        $dt = \DateTime::createFromFormat($pattern, $validator->getValue($attribute));
        if ($dt === false || \DateTime::getLastErrors()['warning_count']) {
            $message = $this->getMessage(
                Messages::format('The :attribute is not a valid datetime', $attribute)
            );
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        }
        return true;
    }
}
