<?php
namespace PhalconX\Mvc\Model;

use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconX\Helper\ArrayHelper;
use Phalcon\Mvc\Model\Exception;

/**
 * Fix timestamp behavior:
 *
 *  1. won't change create timestamp if it is not empty
 *  2. update timestamp when changed (call PhalconX\Mvc\Model::isChanged)
 */
class TimestampBehavior extends Behavior implements BehaviorInterface
{
    public function notify($type, ModelInterface $model)
    {
        if (!$this->mustTakeAction($type)) {
            return;
        }
        $options = $this->getOptions($type);
        if (is_array($options) && isset($options['field'])) {
            // do not change create timestamp if not empty
            if ($type == 'beforeCreate' && $model->readAttribute($options['field'])) {
                return;
            }
            // update timestamp when changed
            if ($type == 'beforeSave' && method_exists($model, 'isChanged')
                && !$model->isChanged()) {
                return;
            }
            $format = ArrayHelper::fetch($options, 'format', 'Y-m-d H:i:s');
            $timestamp = is_string($format)
                ? date($format)
                : call_user_func($format);
            $model->writeAttribute($options['field'], $timestamp);
        } else {
            throw new Exception("Invalid timestamp options for model " . get_class($model));
        }
    }
}
