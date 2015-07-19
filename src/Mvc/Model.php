<?php
namespace PhalconX\Mvc;

use Phalcon\Mvc\Model as BaseModel;

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
        }
        return static::findFirst($pk);
    }
}
