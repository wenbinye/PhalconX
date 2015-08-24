<?php
namespace PhalconX\Mvc\Model;

use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconX\Util;
use PhalconX\Exception;

class TimestampBehavior extends Behavior implements BehaviorInterface
{
    public function notify($type, ModelInterface $model)
    {
        if (!$this->mustTakeAction($type)) {
            return;
        }
        $options = $this->getOptions($type);
        if (is_array($options) && isset($options['field'])) {
            if ($type == 'beforeCreate' && $model->readAttribute($options['field'])) {
                return;
            }
            $format = Util::fetch($options, 'format', 'Y-m-d H:i:s');
            $timestamp = is_string($format)
                ? date($format)
                : call_user_func($format);
            $model->writeAttribute($options['field'], $timestamp);
        } else {
            throw new Exception("Invalid timestamp options for model " . get_class($model));
        }
    }
}
