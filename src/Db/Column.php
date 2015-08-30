<?php
namespace PhalconX\Db;

use Phalcon\Db\Column as BaseColumn;

class Column extends BaseColumn
{
    protected $comment;

    public function __construct($name, array $definition)
    {
        parent::__construct($name, $definition);
        if (isset($definition['comment'])) {
            $this->setComment($definition['comment']);
        }
    }
    
    public static function copy(BaseColumn $column)
    {
        $definition = self::filterArray(get_object_vars($column));
        return new self($definition['name'], $definition);
    }
    
    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function toArray()
    {
        return self::filterArray(get_object_vars($this));
    }

    protected static function filterArray($arr)
    {
        $definition = [];
        foreach ($arr as $name => $val) {
            if (!isset($val)
                || ($name == '_scale' && $val == 0)) {
                continue;
            }
            if ($name[0] == '_') {
                $name = substr($name, 1);
            }
            $definition[$name] = $val;
        }
        return $definition;
    }
}
