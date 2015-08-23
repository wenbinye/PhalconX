<?php
namespace PhalconX\Db;

use Phalcon\Db;
use Phalcon\Exception;
use Phalcon\Db\Index;

class DbHelper
{
    private $dialects = [];
    
    public function describeColumns($conn, $table, $schema = null)
    {
        $sql = $this->getDialect($conn)->showFullColumns($table, $schema);
        $comments = [];
        foreach ($conn->fetchAll($sql, Db::FETCH_ASSOC) as $row) {
            $comments[$row['Field']] = $row['Comment'];
        }
        $columns = [];
        foreach ($conn->describeColumns($table, $schema) as $col) {
            $column = Column::copy($col);
            if (!empty($comments[$col->getName()])) {
                $column->setComment($comments[$col->getName()]);
            }
            $columns[] = $column;
        }
        return $columns;
    }

    public function describeIndexes($conn, $table, $schema = null)
    {
        $dialect = $conn->getDialectType();
        $method = 'describeIndexes' . $dialect;
        if (!method_exists($this, $method)) {
            throw new Exception("Describe index for $dialect is not implemented yet");
        }
        return $this->$method($conn, $table, $schema);
    }

    private function describeIndexesMysql($conn, $table, $schema)
    {
        $sql = $this->getDialect($conn)->describeIndexes($table, $schema);
        $indexes = [];
        foreach ($conn->fetchAll($sql, Db::FETCH_ASSOC) as $row) {
            $keyName = $row['Key_name'];
            if (!isset($indexes[$keyName])) {
                $index = ['columns' => [], 'type' => null];
            } else {
                $index = $indexes[$keyName];
            }
            $index['columns'][] = $row['Column_name'];
            if (!$row['Non_unique']) {
                $index['type'] = 'UNIQUE';
            }
            $indexes[$keyName] = $index;
        }
        $objects = [];
        foreach ($indexes as $name => $index) {
            $objects[$name] = new Index($name, $index['columns'], $index['type']);
        }
        return $objects;
    }
    
    public function createTable($conn, $table, $schema, array $definition)
    {
        if (empty($definition['columns'])) {
            throw new Exception("The table must contain at least one column");
        }
        $conn->execute($this->getDialect($conn)->createTable($table, $schema, $definition));
    }

    
    public function getDialect($conn)
    {
        $type = $conn->getDialectType();
        if (in_array($type, ['mysql'])) {
            if (!isset($this->dialects[$type])) {
                $clz = 'PhalconX\Db\Dialect\\' . ucfirst($type);
                $this->dialects[$type] = new $clz;
            }
            return $this->dialects[$type];
        } else {
            return $conn->getDialect();
        }
    }
}
