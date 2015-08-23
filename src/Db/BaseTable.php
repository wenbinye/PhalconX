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

    abstract public function toSQL(AdapterInterface $db, $options = null);
}
