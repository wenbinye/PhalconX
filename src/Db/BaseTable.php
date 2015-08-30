<?php
namespace PhalconX\Db;

use PhalconX\Mvc\SimpleModel;
use Phalcon\Db\AdapterInterface;

abstract class BaseTable extends SimpleModel
{
    public $name;

    public $schema;

    public function getName()
    {
        return $this->schema ? $this->schema . '.' . $this->name
            : $this->name;
    }

    public static function parseName($name)
    {
        $parts = explode('.', $name);
        if (count($parts) > 1) {
            $def['name'] = $parts[1];
            $def['schema'] = $parts[0];
        } else {
            $def['name'] = $parts[0];
        }
        return $def;
    }
    
    abstract public function toSQL(AdapterInterface $db, $options = null);
}
