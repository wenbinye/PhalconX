<?php
namespace PhalconX\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use PhalconX\Messages;

class IsArray extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $value = $validator->getValue($attribute);
        if (!is_array($value)) {
            $message = $this->getMessage(
                Messages::format('The :attribute must be an array', $attribute)
            );
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        }
        $elemValidator = $this->getOption('element');
        if (isset($elemValidator)) {
            $validation = new Validation;
            $data = array();
            foreach ($value as $i => $elem) {
                $key = sprintf('%s[%d]', $attribute, $i);
                $validation->add($key, $elemValidator);
                $data[$key] = $elem;
            }
            $errors = $validation->validate($data);
            if (count($errors)) {
                foreach ($errors as $message) {
                    $validator->appendMessage($message);
                }
                return false;
            }
        }
        return true;
    }
}
