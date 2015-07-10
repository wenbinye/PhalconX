<?php
namespace PhalconX\Mvc;

use PhalconX\Mvc\Model\TimestampBehavior as Timestampable;

abstract class Model extends \Phalcon\Mvc\Model
{
    protected $id;
    protected $create_time = 0;
    protected $update_time = 0;

    public function initialize()
    {
        $this->addBehavior(new Timestampable(array(
            'beforeCreate' => array(
                'field' => 'create_time',
                'format' => 'Y-m-d H:i:s'
            ),
            'beforeSave' => array(
                'field' => 'update_time',
                'format' => 'Y-m-d H:i:s'
            )
        )));
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getCreateTime()
    {
        return $this->create_time;
    }

    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;
        return $this;
    }

    public function getUpdateTime()
    {
        return $this->update_time;
    }

    public function setUpdateTime($updateTime)
    {
        $this->update_time = $updateTime;
        return $this;
    }

    public static function findBy($attrs)
    {
        $conditions = '';
        $sep = '';
        foreach ($attrs as $name => $value) {
            $conditions .= $sep . "$name=:$name:";
            $sep = ' AND ';
        }
        return static::findFirst(array(
            'conditions' => $conditions,
            'bind' => $attrs
        ));
    }
}
