<?php
namespace PhalconX\Db;

use Phalcon\Text;
use PhalconX\Util;
use PhalconX\Mvc\SimpleModel;
use PhalconX\Db\Column;
use Phalcon\Validation\Message;
use PhalconX\Exception;
use PhalconX\Exception\ValidationException;

class ColumnDefinition extends SimpleModel
{
    public $name;

    public $type;

    public $size = 0;

    public $scale = 0;

    public $isNumeric = false;

    public $unsigned = false;

    public $notNull = false;

    public $autoIncrement = false;

    public $first = false;

    public $after;

    public $bindType = 'str';

    public $default;

    public $comment;

    public static function fromColumn(Column $column)
    {
        $data = $column->toArray();
        if (isset($data['type']) && is_integer($data['type'])) {
            $data['type'] = self::getTypeName($data['type']);
        }
        if (isset($data['bindType']) && is_integer($data['bindType'])) {
            $data['bindType'] = self::getBindName($data['bindType']);
        }
        
        return new self($data);
    }
    
    public function toColumn()
    {
        $arr = parent::toArray();
        if ($this->scale == 0) {
            unset($arr['scale']);
        }
        $arr['type'] = constant(Column::CLASS . '::TYPE_' . strtoupper($arr['type']));
        $arr['bindType'] = constant(Column::CLASS . '::BIND_PARAM_' . strtoupper($arr['bindType']));
        return new Column($arr['name'], $arr);
    }

    public function toArray()
    {
        static $default;
        if (!$default) {
            $default = new self;
        }
        $arr = parent::toArray();
        foreach ($arr as $name => $val) {
            if ($val === $default->$name) {
                unset($arr[$name]);
            }
        }
        return $arr;
    }
    
    public static function getTypeName($type)
    {
        static $types;
        if (!$types) {
            $types = self::getColumnsConstant('TYPE_');
        }
        return Util::fetch($types, $type);
    }

    public static function getBindName($bindType)
    {
        static $types;
        if (!$types) {
            $types = self::getColumnsConstant('BIND_PARAM_');
        }
        return Util::fetch($types, $bindType);
    }

    private static function getColumnsConstant($prefix)
    {
        $constants = [];
        $len = strlen($prefix);
        $refl = new \ReflectionClass(Column::CLASS);
        foreach ($refl->getConstants() as $name => $val) {
            if (Text::startsWith($name, $prefix)) {
                $constants[$val] = strtolower(substr($name, $len));
            }
        }
        return $constants;
    }

    public function isSame(ColumnDefinition $other)
    {
        return $this->type == $other->type
            && $this->size == $other->size
            && $this->scale == $other->scale
            && $this->default === $other->default
            && $this->unsigned == $other->unsigned
            && $this->notNull == $other->notNull
            && $this->comment == $other->comment;
    }

    public function check()
    {
        $errors = new Message\Group;
        if (!$this->name) {
            $errors->appendMessage(new Message("Column name is required", 'name'));
        }
        if (!$this->type) {
            $errors->appendMessage(new Message("Column type is required", 'type'));
        }
        if (!defined(Column::CLASS . '::TYPE_' . strtoupper($this->type))) {
            $errors->appendMessage(new Message(
                sprintf("Column type '%s' is not defined", $this->type),
                'type'
            ));
        }
        if ($this->bindType && !defined(Column::CLASS . '::BIND_PARAM_' . strtoupper($this->bindType))) {
            $errors->appendMessage(new Message(
                sprintf("Column bind type '%s' is not defined", $this->bindType),
                'bindType'
            ));
        }
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    public function getDefinition()
    {
        $data = $this->toArray();
        $def = [];
        if (isset($data['size'])) {
            $scale = (isset($data['scale']) ? ','.$data['scale'] : '');
            $type = sprintf('%s(%s%s)', $data['type'], $data['size'], $scale);
        } else {
            $type = $data['type'];
        }
        if (!empty($data['bindType'])) {
            $type .= '=' . $data['bindType'];
        }
        $def[] = $type;
        foreach (['isNumeric', 'unsigned', 'notNull', 'autoIncrement', 'first'] as $name) {
            if (!empty($data[$name])) {
                $def[] = $name;
            }
        }
        if (!empty($data['after'])) {
            $def[] = 'after=' . $data['after'];
        }
        $other = [];
        if (isset($this->default)) {
            $other['default'] = $this->default;
        }
        if (isset($this->comment)) {
            $other['comment'] = $this->comment;
        }
        if ($other) {
            $def[] = json_encode($other, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
        }
        return implode(' ', $def);
    }

    public static function create($name, $definition)
    {
        if (!is_string($definition)) {
            throw new Exception("Invalid column definition for column '$name'");
        }
        $def = [];
        $pos = strpos($definition, ' {');
        if ($pos !== false) {
            $def = json_decode(substr($definition, $pos+1), true);
            $definition = substr($definition, 0, $pos);
        }
        $data = explode(' ', $definition);
        $type = array_shift($data);
        $pos = strpos($type, '=');
        if ($pos !== false) {
            $def['bindType'] = substr($type, $pos+1);
            $type = substr($type, 0, $pos);
        }
        $parts = preg_split('/[\(\),]/', $type);
        $def['type'] = $parts[0];
        if (count($parts) > 1) {
            $def['size'] = $parts[1];
            $def['scale'] = $parts[2];
            if (empty($def['scale'])) {
                unset($def['scale']);
            }
        }
        foreach ($data as $entry) {
            $parts = explode('=', $entry, 2);
            $def[$parts[0]] = Util::fetch($parts, 1, true);
        }
        $def['name'] = $name;
        return new self($def);
    }
}
