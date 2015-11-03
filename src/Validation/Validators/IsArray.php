<?php
namespace PhalconX\Validation\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Validator as BaseValidator;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Message;

class IsArray extends BaseValidator implements ValidatorInterface
{
    public function validate(Validation $validation, $field)
    {
        $value = $validation->getValue($field);
        if (!is_array($value)) {
            $label = $this->getOption('label');
            if (empty($label)) {
                $label = $validation->getLabel($field);
            }
            $message = $this->getOption('message');
            if (empty($message)) {
                $messageName = 'NotIsArray';
                $message = $validation->getDefaultMessage($messageName);
                if (empty($message)) {
                    $message = Validator::getDefaultMessage($messageName);
                }
            }
            $message = strtr($message, [':field' => $label]);
            $validation->appendMessage(new Message($message, $field));
            return false;
        }
        $elemValidators = $this->getOption('validators');
        if (isset($elemValidators)) {
            $elemValidation = new Validation;
            $data = array();
            foreach ($value as $i => $elem) {
                $key = sprintf('%s[%d]', $field, $i);
                $elemValidation->rules($key, $elemValidators);
                $data[$key] = $elem;
            }
            $errors = $elemValidation->validate($data);
            if (count($errors)) {
                foreach ($errors as $message) {
                    $validation->appendMessage($message);
                }
                return false;
            }
        }
        return true;
    }
}
