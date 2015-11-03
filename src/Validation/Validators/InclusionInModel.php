<?php
namespace PhalconX\Validation\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Validator as BaseValidator;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Message;

class InclusionInModel extends BaseValidator implements ValidatorInterface
{
    public function validate(Validation $validation, $field)
    {
        $value = $validation->getValue($field);
        $model = $this->getOption('model');
        if (!$model) {
            throw new Exception("Model must be set");
        }
        $attribute = $this->getOption('attribute', $field);
        $num = $model::count([
            $attribute . "= :value:",
            "bind" => [
                'value' => $value
            ]
        ]);
        if ($num > 0) {
            return true;
        }
        $label = $this->getOption('label');
        if (empty($label)) {
            $label = $validation->getLabel($field);
        }
        $message = $this->getOption('message');
        if (empty($message)) {
            $messageName = 'NotInclusionInModel';
            $message = $validation->getDefaultMessage($messageName);
            if (empty($message)) {
                $message = Validator::getDefaultMessage($messageName);
            }
        }
        $message = strtr($message, [':field' => $label, ':model' => $model]);
        $validation->appendMessage(new Message($message, $field));
        return false;
    }
}
