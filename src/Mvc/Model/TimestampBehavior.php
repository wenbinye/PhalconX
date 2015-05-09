<?php
namespace PhalconX\Mvc\Model;

use Phalcon\Mvc\Model\Behavior;

class TimestampBehavior extends Behavior
{
    public function notify($type, $model)
    {
        if ( !$this->mustTakeAction($type) ) {
            return;
        }
        $options = $this->getOptions($type);
        if ( is_array($options) ) {
            if ( $type == 'beforeCreate' && $model->readAttribute($options['field']) ) {
                return;
            }
            $format = $options['format'];
            $timestamp = is_string($options['format'])
                ? date($options['format'])
                : call_user_func($options['format']);
            $model->writeAttribute($options['field'], $timestamp);
        }
    }
}
