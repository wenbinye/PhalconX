<?php
namespace PhalconX\Db\Schema;

use Phalcon\Db;
use PhalconX\Mvc\SimpleModel;
use PhalconX\Exception\ValidationException;
use Phalcon\Validation\Message;

/**
 * index type
 */
class Index extends SimpleModel
{
    const PRIMARY = 'PRIMARY';

    /**
     * @var string index name
     */
    public $name;

    /**
     * @var string index type, accept only 'UNIQUE'
     */
    public $type;

    /**
     * @var array columns
     */
    public $columns;

    /**
     * convert from Phalcon\Db\Index object
     */
    public static function fromIndex(Db\Index $index)
    {
        return new self([
            'name' => $index->getName(),
            'columns' => $index->getColumns(),
            'type' => $index->getType()
        ]);
    }

    /**
     * create from definition
     *   PRIMARY KEY(col1, ...)
     *   UNIQUE KEY(col1, ...)
     *   KEY(col1, ...)
     */
    public static function create($name, $definition)
    {
        if (preg_match('/^(.*?)KEY\s*\((.*?)\)/', $definition, $matches)) {
            return new self([
                'name' => $name,
                'columns' => preg_split('/\s*,\s*/', $matches[2]),
                'type' => trim($matches[1])
            ]);
        } else {
            throw new \InvalidArgumentException("Invalid index definition '$definition'");
        }
    }

    /**
     * return Phalcon\Db\Index
     */
    public function toIndex()
    {
        return new Db\Index($this->name, $this->columns, $this->type);
    }

    public function toArray()
    {
        $arr = parent::toArray();
        if ($arr['name'] == self::PRIMARY) {
            unset($arr['type']);
        }
        return $arr;
    }

    public function check()
    {
        $errors = new Message\Group;
        if (empty($this->columns)) {
            $errors->appendMessage(new Message("Index columns is required", 'columns'));
        }
        if (!is_array($this->columns)) {
            $errors->appendMessage(new Message("Index columns should be an array", 'columns'));
        }
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    /**
     * compare with index
     *
     * @param Index $other
     * @param array $renamedColumns
     * @return boolean
     */
    public function isSame(Index $other, $renamedColumns = null)
    {
        if ($renamedColumns) {
            $columns = [];
            foreach ($this->columns as $col) {
                $columns[] = isset($renamedColumns[$col]) ? $renamedColumns[$col]->name : $col;
            }
        } else {
            $columns = $this->columns;
        }
        if ($columns != $other->columns) {
            return false;
        }
        if ($this->name == self::PRIMARY || $other->name == self::PRIMARY) {
            return $this->name == $other->name;
        } else {
            return $this->type == $other->type;
        }
    }

    /**
     * @return boolean
     */
    public function isPrimaryKey()
    {
        return $this->name == self::PRIMARY;
    }

    /**
     * @return string
     *   PRIMARY KEY(col1, ...)
     *   UNIQUE KEY(col1, ...)
     *   KEY(col1, ...)
     */
    public function getDefinition()
    {
        if ($this->isPrimaryKey()) {
            $type = self::PRIMARY;
        } else {
            $type = $this->type;
        }
        if ($type) {
            $type .= ' ';
        }
        return sprintf('%sKEY(%s)', $type, implode(',', $this->columns));
    }
}
