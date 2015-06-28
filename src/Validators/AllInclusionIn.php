<?php
namespace Ruyitao\Phalcon;

use Phalcon\Validation\Message;

/**
 * 检查数组中每个值都在 domain 指定的数组中
 *
 * <code>
 * $selections = new Text('choices');
 * $selections->addValidator(new AllInclusionIn(array(
 *    'domain' => array('choice1', 'choice2')
 * )));
 * </code>
 */
class AllInclusionIn extends BaseValidator
{
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $value = $validator->getValue($attribute);
        $domain = $this->getOption('domain');
        if (!is_array($value)) {
            $validator->appendMessage(new Message('Value is not an array', $attribute));
            return false;
        }
        foreach ($value as $val) {
            if (!in_array($val, $domain)) {
                $msg = $this->getOptionDef('message', 'Value must be part of list: ' . implode(', ', $domain));
                $validator->appendMessage(new Message($msg, $attribute));
                return false;
            }
        }
        return true;
    }
}
