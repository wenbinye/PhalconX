<?php
namespace PhalconX\Validation\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Validator as BaseValidator;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Message;

class Range extends BaseValidator implements ValidatorInterface
{
    public function validate(Validation $validation, $field)
    {
        $value = $validation->getValue($field);
        $max = $this->getOption('max');
        $min = $this->getOption('min');
        if (isset($min) && isset($max) && $min > $max) {
            throw new \InvalidArgumentException("The minimum value should less than maximum value");
        }

        $maxExceed = false;
        $minExceed = false;
        if (isset($max)) {
            if ($this->getOption('exclusiveMaximum', false)) {
                $maxExceed = ($value >= $max);
            } else {
                $maxExceed = ($value > $max);
            }
        }
        if (isset($min)) {
            if ($this->getOption('exclusiveMinimum', false)) {
                $minExceed = ($value <= $min);
            } else {
                $minExceed = ($value < $min);
            }
        }
        if ($maxExceed || $minExceed) {
            $label = $this->getOption('label');
            if (empty($label)) {
                $label = $validation->getLabel($field);
            }
            if ($maxExceed) {
                $message = $this->getOption('messageMaximum');
                $messageName = 'TooLarge';
                $compareTo = $max;
            } else {
                $message = $this->getOption('messageMinimum');
                $messageName = 'TooSmall';
                $compareTo = $min;
            }
            if (empty($message)) {
                $message = $validation->getDefaultMessage($messageName);
                if (empty($message)) {
                    $message = Validator::getDefaultMessage($messageName);
                }
            }
            $message = strtr($message, [':field' => $label, ':value' => $compareTo]);
            $validation->appendMessage(new Message($message, $field));
            return false;
        } else {
            return true;
        }
    }
}
