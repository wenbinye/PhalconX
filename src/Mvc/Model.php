<?php
namespace PhalconX\Mvc;

use Phalcon\Mvc\Model as BaseModel;
use Phalcon\Mvc\Model\Criteria;

abstract class Model extends BaseModel
{
    public static function findPk($pk)
    {
        if (is_array($pk)) {
            $conditions = '';
            $sep = '';
            foreach ($pk as $name => $value) {
                $conditions .= $sep . "$name=:$name:";
                $sep = ' AND ';
            }
            $pk = array(
                'conditions' => $conditions,
                'bind' => $pk
            );
        } elseif ($pk instanceof Criteria) {
            $pk = $pk->getParams();
        }
        return static::findFirst($pk);
    }
}
