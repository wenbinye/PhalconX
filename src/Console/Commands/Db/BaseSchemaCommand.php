<?php
namespace PhalconX\Console\Commands\Db;

use PhalconX\Console\Annotations\Option;
use PhalconX\Console\Annotations\Argument;
use PhalconX\Helper\ExportHelper;
use PhalconX\Db\Schema\Table;

abstract class BaseSchemaCommand extends BaseCommand
{
    /**
     * @Option(optional, shortcut='-f', desc="Output format, support sql/yaml/json/php")
     */
    public $format = 'yaml';

    /**
     * @Option(optional, shortcut='-d', help="Database schema name")
     */
    public $dbname;

    /**
     * @Argument(is_array, desc="Table names")
     */
    public $tables;

    private static $ALL_TABLES;
    
    protected function export($data)
    {
        $arr = [];
        if ($this->isOutputSql()) {
            $db = $this->getConnection();
            foreach ($data as $one) {
                $arr[] = $one->toSQL($db);
            }
        } else {
            foreach ($data as $one) {
                $arr[$one->getName()] = $one->getDefinition();
            }
        }
        return $arr;
    }

    protected function exportTables($tables)
    {
        if ($this->isOutputSql()) {
            echo implode(";\n", $this->export($tables)), ";\n";
        } else {
            echo ExportHelper::export($this->export($tables), $this->format);
        }
    }

    protected function isOutputSql()
    {
        return $this->format == 'sql';
    }

    protected function getAllTables()
    {
        $schema = $this->dbname ? $this->dbname : '';
        if (!isset(self::$ALL_TABLES[$schema])) {
            self::$ALL_TABLES[$schema] = $this->getConnection()->listTables($schema);
        }
        return self::$ALL_TABLES[$schema];
    }

    protected function loadTables($source)
    {
        $tables = [];
        if (!isset($source)) {
            $db = $this->getConnection();
            foreach ($this->getAllTables() as $table) {
                if ($this->tables && !in_array($table, $this->tables)) {
                    continue;
                }
                $tables[$table] = Table::describeTable($db, $table, $this->dbname);
            }
        } else {
            $data = ExportHelper::loadFile($source);
            foreach ($data as $name => $def) {
                if ($this->tables && !in_array($name, $this->tables)) {
                    continue;
                }
                $tables[$name] = Table::create($name, $def);
            }
        }
        foreach ($tables as $table) {
            if (array_key_exists('auto_increment', $table->options)) {
                unset($table->options['auto_increment']);
            }
        }
        return $tables;
    }
}
