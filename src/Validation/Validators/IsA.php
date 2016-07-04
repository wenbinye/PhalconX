<?php
namespace PhalconX\Validation\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Validator as BaseValidator;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Message;
use Phalcon\Validation\Exception;
use PhalconX\Validation\Form;
use PhalconX\Exception\ValidationException;

class IsA extends BaseValidator implements ValidatorInterface
{
    private $form;
    
    public function __construct(\PhalconX\Validation\Validation $form, $options)
    {
        $this->form = $form;
        parent::__construct($options);
    }
    
    public function validate(Validation $validation, $field)
    {
        $value = $validation->getValue($field);
        $class = $this->getOption('class');
        if (empty($class)) {
            throw new Exception("Class should be set");
        }
        if (!is_a($value, $class)) {
            $label = $this->getOption('label');
            if (empty($label)) {
                $label = $validation->getLabel($field);
            }
            $message = $this->getOption('message');
            if (empty($message)) {
                $messageName = 'NotIsA';
                $message = $validation->getDefaultMessage($messageName);
                if (empty($message)) {
                    $message = Validator::getDefaultMessage($messageName);
                }
            }
            $message = strtr($message, [':field' => $label, ':class' => $class]);
            $validation->appendMessage(new Message($message, $field));
            return false;
        }
        try {
            $this->form->validate($value);
            return true;
        } catch (ValidationException $e) {
            foreach ($e->getErrors() as $error) {
                $validation->appendMessage(new Message($error->getMessage(), $field . '.' . $error->getField()));
            }
            return false;
        }
    }
}
