<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;
use PhalconX\Messages;

class Range extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $value = $validator->getValue($attribute);
        $max = $this->getOption('maximum');
        $min = $this->getOption('minimum');
        if ($min > $max) {
            throw new \InvalidArgumentException("The minimum value should less than maximum value");
        }

        $maxExceed = false;
        $minExceed = false;
        if (isset($max)) {
            if ($this->getOptionWithDefault('exclusiveMaximum')) {
                $maxExceed = ($value >= $max);
            } else {
                $maxExceed = ($value > $max);
            }
        }
        if (isset($min)) {
            if ($this->getOptionWithDefault('exclusiveMinimum')) {
                $minExceed = ($value <= $min);
            } else {
                $minExceed = ($value < $min);
            }
        }
        if ($maxExceed || $minExceed) {
            if ($maxExceed) {
                $message = $this->getMessage(
                    Messages::format('The :attribute must less than :value', $attribute, $max)
                );
            } else {
                $message = $this->getMessage(
                    Messages::format('The :attribute must greater than :value', $attribute, $min)
                );
            }
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        } else {
            return true;
        }
    }
}
