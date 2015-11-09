<?php
namespace PhalconX\Db;

use Phalcon\Db;
use Phalcon\Exception;
use Phalcon\Db\Index;
use Phalcon\Text;
use PhalconX\Helper\Mixin;

/**
 * helper functions for phalcon db connection
 *
 * <code>
 *   use PhalconX\Helper\Mixin;
 *
 *   $db = Mixin::create($di['db'], new DbHelper);
 *   $db = DbHelper::getConnection('mysql:host=localhost;username=user;password=pass;dbname=testdb'
 * </code>
 */
class DbHelper
{
    private $dialects = [];

    /**
     * Adds comment to column
     *
     * @param DbAdapter $conn
     * @param string $table
     * @param string $schema
     * @return Column[]
     */
    public function describeColumns($conn, $table, $schema = null)
    {
        $dialect = $this->getDialect($conn);
        if (method_exists($dialect, 'showFullColumns')) {
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
        } else {
            return $conn->describeColumns($table, $schema);
        }
    }

    /**
     * Describes table indexes
     *
     * @param DbAdapter $conn
     * @param string $table
     * @param string $schema
     * @return Index[]
     */
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

    /**
     * creates database table
     */
    public function createTable($conn, $table, $schema, array $definition)
    {
        if (empty($definition['columns'])) {
            throw new Exception("The table must contain at least one column");
        }
        $conn->execute($this->getDialect($conn)->createTable($table, $schema, $definition));
    }

    /**
     * @return Dialect
     */
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

    /**
     * @param string $dsn mysql:host=localhost;port=3307;dbname=testdb
     *   sqlite::memory:
     *   sqlite:/opt/databases/mydb.sq3
     */
    public static function parseDsn($dsn)
    {
        if (Text::startsWith($dsn, 'sqlite:')) {
            return [
                'adapter' => 'sqlite',
                'dbname' => substr($dsn, strlen('sqlite:'))
            ];
        } elseif (Text::startsWith($dsn, 'mysql:')) {
            $options = ['adapter' => 'mysql'];
            foreach (explode(';', substr($dsn, strlen('mysql:'))) as $pair) {
                list($key, $val) = explode('=', $pair, 2);
                if (in_array($key, ['host', 'dbname', 'port', 'username', 'password', 'dialectClass'])) {
                    $options[$key] = $val;
                } else {
                    $options['dsn'][$key] = $val;
                }
            }
            return $options;
        } else {
            throw new Exception("Not implement dsn format: '$dsn'");
        }
    }

    /**
     * creates database connect by dsn
     *
     * @param string $dsn
     * @return DbAdapter
     */
    public static function getConnection($dsn)
    {
        if (!is_array($dsn)) {
            $dsn = self::parseDsn($dsn);
        }
        if (!isset($dsn['adapter'])) {
            throw new Exception("database adapter is missing for " . json_encode($dsn));
        }
        $class = 'Phalcon\Db\Adapter\Pdo\\' . ucfirst($dsn['adapter']);
        return Mixin::create(new $class($dsn), new self);
    }
}
