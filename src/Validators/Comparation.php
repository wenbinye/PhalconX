<?php
namespace PhalconX\Validators;

use Phalcon\Validation\Message;

/**
 * 检查值是否大于或小于指定值
 *
 * <code>
 * $hour = new Text('hour');
 * $hour->addValidator(new Comparation(array('type' => '<=', 'with' => 23)));
 * $hour->addValidator(new Comparation(array('type' => '>=', 'with' => 0)));
 * </code>
 */
class Comparation extends BaseValidator
{
    /**
     * type: '>=', '<=', '>', '<'
     * with
     */
    public function validate($validator, $attribute)
    {
        $the_value = $validator->getValue($this->getOption('with'));
        $value = $validator->getValue($attribute);
        $type = $this->getOption('type');

        switch ( $type ) {
        case '>':
            $match = $value > $the_value;
            break;
        case '>=':
            $match = $value >= $the_value;
            break;
        case '<':
            $match = $value < $the_value;
            break;
        case '<=':
            $match = $value <= $the_value;
            break;
        default:
            throw new \InvalidArgumentException("unknown compare type '{$type}'");
        }
        if ( !$match ) {
            $message = $this->getMessage($attribute .' is not ' . $type . ' ' . $this->getOption('with'));
            $validator->appendMessage(new Message($message, $attribute));
        }
        return $match;
    }
}
