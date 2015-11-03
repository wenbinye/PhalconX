<?php
namespace PhalconX\Validation\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Validator as BaseValidator;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Message;

abstract class Validator extends BaseValidator implements ValidatorInterface
{
    protected $defaultMessageName;

    protected static $DEFAULT_MESSAGES = [
        'TooLarge' => "Field :field must not greater than :value",
        'TooSmall' => "Field :field must not smaller than :value",
        'NotBoolean' => "Field :field is not valid boolean value",
        'NotDatetime' => "Field :field does not match datetime pattern ':pattern'",
        'NotIsA' => 'Field :field is not instance of :class',
        'NotIsArray' => 'Field :field is not an array',
        'NotInclusionInModel' => 'Field :field is in table for :model'
    ];
    
    abstract protected function check($value, $validation);

    protected function getDefaultMessageName()
    {
        return $this->defaultMessageName;
    }

    public static function getDefaultMessage($name)
    {
        if (isset(self::$DEFAULT_MESSAGES[$name])) {
            return self::$DEFAULT_MESSAGES[$name];
        } else {
            throw new \InvalidArgumentException("Unknown message name '$name'");
        }
    }

    protected function getMessageVars()
    {
        return [];
    }
    
    public function validate(Validation $validation, $field)
    {
        $value = $validation->getValue($field);
        if ($this->check($value, $validation)) {
            return true;
        }
        $label = $this->getOption('label');
        if (empty($label)) {
            $label = $validation->getLabel($field);
        }
        $message = $this->getOption('message');
        if (empty($message)) {
            $messageName = $this->getDefaultMessageName();
            $message = $validation->getDefaultMessage($messageName);
            if (empty($message)) {
                $message = self::getDefaultMessage($messageName);
            }
        }
        $message = strtr($message, array_merge([':field' => $label], $this->getMessageVars()));
        $validation->appendMessage(new Message($message, $field));
        return false;
    }
}
