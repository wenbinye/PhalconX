<?php
namespace PhalconX\Db\Dialect;

use Phalcon\Db\Dialect\Mysql as BaseMysql;
use Phalcon\Db\ColumnInterface;
use PhalconX\Db\Column;

/**
 * extends mysql dialect
 */
class Mysql extends BaseMysql
{
    /**
     * Gets full columns definitions
     */
    public function showFullColumns($table, $schema = null)
    {
        return "SHOW FULL COLUMNS FROM `" . ($schema ? $schema . '`.`' : '') . $table . "`";
    }

    /**
     * supports comment and tinyint
     */
    public function getColumnDefinition(ColumnInterface $column)
    {
        $sql = parent::getColumnDefinition($column);
        if ($column instanceof Column && $column->getComment()) {
            $sql .= " COMMENT \"" . addcslashes($column->getComment(), '"') . "\"";
        }
        $def = $column->getDefault();
        // empty value will ignore when create table
        if (isset($def) && in_array($def, [0, '', '0'])) {
            $sql .= ' DEFAULT "' . $def . '"';
        }
        if ($column->getType() == Column::TYPE_INTEGER
            && $column->getSize() == 4) {
            $sql = preg_replace('/^INT/', 'TINYINT', $sql);
        }
        return $sql;
    }

    public function modifyColumn($table, $schema, ColumnInterface $column, ColumnInterface $current = null)
    {
        $sql = 'ALTER TABLE `' . ($schema ? $schema . '`.`' : '') . $table . '` ';
        if ($current && $current->getName() != $column->getName()) {
            $sql .= sprintf('CHANGE `%s` `%s` ', $current->getName(), $column->getName());
        } else {
            $sql .= sprintf('MODIFY `%s` ', $column->getName());
        }
        $sql .= $this->getColumnDefinition($column);
        // getColumnDefinition will not add default when value is empty
        $def = $column->getDefault();
        if (!empty($def)) {
            $sql .= ' DEFAULT "' . addcslashes($def, '"') . '"';
        }
        if ($column->isNotNull()) {
            $sql .= " NOT NULL";
        }
        return $sql;
    }

    public function addColumn($table, $schema, ColumnInterface $column)
    {
        $sql = 'ALTER TABLE `' . ($schema ? $schema . '`.`' : '') . $table . '` ADD `'
            . $column->getName() . '` '
            . $this->getColumnDefinition($column);
        // getColumnDefinition will not add default when value is empty
        $def = $column->getDefault();
        if (!empty($def)) {
            $sql .= ' DEFAULT "' . addcslashes($def, '"') . '"';
        }
        if ($column->isNotNull()) {
            $sql .= " NOT NULL";
        }
        return $sql;
    }
    
    protected function _getTableOptions(array $def)
    {
        if (isset($def['options'])) {
            $options = $def['options'];
            $def['options'] = array_combine(
                array_map('strtoupper', array_keys($options)),
                array_values($options)
            );
        }
        return parent::_getTableOptions($def);
    }
}
