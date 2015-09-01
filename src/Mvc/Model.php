<?php
namespace PhalconX\Mvc;

use Phalcon\Db\Column;
use Phalcon\Mvc\Model as BaseModel;
use Phalcon\Mvc\Model\Criteria;
use PhalconX\Util;

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

    public function isChanged()
    {
        $snapshot = $this->_snapshot;
        if (!is_array($snapshot)) {
            return true;
        }
        $metadata = $this->getModelsMetaData();
        $attrs = $metadata->getNonPrimaryKeyAttributes($this);
        $automatic = $metadata->getAutomaticUpdateAttributes($this);
        $bindDataTypes = $metadata->getBindTypes($this);
        foreach ($attrs as $name) {
            if (isset($automatic[$name])) {
                continue;
            }
            $value = $this->readAttribute($name);
            $snapshotValue = Util::fetch($snapshot, $name);
            if ($value === null) {
                if ($snapshotValue !== null) {
                    return true;
                }
            } else {
                if ($snapshotValue === null) {
                    return true;
                }
                $bindType = Util::fetch($bindDataTypes, $name);
                switch ($bindType) {
                    case Column::TYPE_DATE:
                    case Column::TYPE_VARCHAR:
                    case Column::TYPE_DATETIME:
                    case Column::TYPE_CHAR:
                    case Column::TYPE_TEXT:
                    case Column::TYPE_VARCHAR:
                    case Column::TYPE_BIGINTEGER:
                        if (((string)$value) !== ((string)$snapshotValue)) {
                            return true;
                        }
                        break;
                    default:
                        if ($value != $snapshotValue) {
                            return true;
                        }
                }
            }
        }
        return false;
    }
}
