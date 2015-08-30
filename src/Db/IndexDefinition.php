<?php
namespace PhalconX\Db;

use PhalconX\Di\Injectable;
use PhalconX\Mvc\SimpleModel;
use Phalcon\Db\Index;
use PhalconX\Exception\ValidationException;
use Phalcon\Validation\Message;
use PhalconX\Exception;

class IndexDefinition extends SimpleModel
{
    const PRIMARY = 'PRIMARY';
    
    public $name;

    public $type;

    public $columns;

    public static function fromIndex(Index $index)
    {
        return new self([
            'name' => $index->getName(),
            'columns' => $index->getColumns(),
            'type' => $index->getType()
        ]);
    }

    public function toIndex()
    {
        return new Index($this->name, $this->columns, $this->type);
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

    public function isSame(IndexDefinition $other, $renamedColumns = null)
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

    public function isPrimaryKey()
    {
        return $this->name == self::PRIMARY;
    }

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

    public static function create($name, $definition)
    {
        if (preg_match('/^(.*?)KEY\((.*?)\)/', $definition, $matches)) {
            return new self([
                'name' => $name,
                'columns' => preg_split('/\s*,\s*/', $matches[2]),
                'type' => trim($matches[1])
            ]);
        } else {
            throw new Exception("Invalid index definition '$definition'");
        }
    }
}
