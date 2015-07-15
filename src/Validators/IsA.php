<?php
namespace PhalconX\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use PhalconX\Messages;
use PhalconX\Util;
use PhalconX\Exception\ValidationException;

class IsA extends BaseValidator
{
    private $validator;
    
    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->validator = Util::service('validator', $options);
    }

    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $value = $validator->getValue($attribute);
        $clz = $this->getOption('class');
        if (!is_a($value, $clz)) {
            $message = $this->getMessage(
                Messages::format('The :attribute is not instance of :class', $attribute, $clz)
            );
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        }
        try {
            $this->validator->validate($value);
            return true;
        } catch (ValidationException $e) {
            foreach ($e->getErrors() as $error) {
                $validator->appendMessage(new Message($error->getMessage(), $attribute . '.' . $error->getField()));
            }
        }
    }
}
