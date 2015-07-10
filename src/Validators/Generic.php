<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;

/**
 * 通用的验证类
 *
 * <code>
 *   new Generic(array(
 *        'validate' => function($value, $validator) {
 *            // do validation
 *            return $isValid;
 *        }
 *   ));
 * </code>
 */
class Generic extends BaseValidator
{
    protected $validator;
    protected $attribute;

    public function getValidator()
    {
        return $this->validator;
    }

    public function getAttribute()
    {
        return $this->attribute;
    }

    public function appendMessage($message)
    {
        return $this->validator->appendMessage($message);
    }
    
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $this->validator = $validator;
        $this->attribute = $attribute;
        $callback = $this->getOption('validate');
        $value = $validator->getValue($attribute);
        $ret = call_user_func_array($callback, array($value, $this));
        if ($ret) {
            return true;
        } else {
            $message = $this->getMessage("$attribute's value is invalid");
            $validator->appendMessage(new Message($message, $attribute));
            return false;
        }
    }
}
