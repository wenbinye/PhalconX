<?php
namespace PhalconX\Validaters;

use Phalcon\Validation\Message;

/**
 * 检查指定字段中必须有一个值不为空
 *
 * <code>
 * // username 或 email 必须有一个值不为空
 * $username = new Text('username');
 * $username->addValidator(new EitherPresenceOf(array(
 *    'with' => array('email')
 * )));
 * </code>
 */
class EitherPresenceOf extends BaseValidator
{
    public function validate($validator, $attribute)
    {
        $attrs = $this->getOption('with');
        $attrs[] = $attribute;

        foreach ( $attrs as $name ) {
            $value = $validator->getValue($name);
            if ( isset($value) && $value !== "" ) {
                return true;
            }
        }
        $message = $this->getMessage("One of " . implode(', ', $attrs) . " is required");
        $validator->appendMessage(new Message($message, $attribute));
        return false;
    }
}
