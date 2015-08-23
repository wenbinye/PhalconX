<?php
namespace PhalconX\Db;

use Phalcon\Db\AdapterInterface;
use PhalconX\Exception;
use PhalconX\Exception\ValidationException;
use Phalcon\Validation\Message;

class TableDefinition extends BaseTable
{
    /**
     * @IsA('ColumnDefinition[]')
     */
    public $columns;

    /**
     * @IsA('IndexDefinition[]')
     */
    public $indexes;

    public $options;

    /**
     * @IsA('ReferenceDefinition[]')
     */
    public $references;

    private static $DEFAULT_SQL_OPTIONS = [
        'auto_increment' => false,
    ];
    
    /**
     * @return TableDefinition
     */
    public static function describeTable(AdapterInterface $db, $table, $schema = null)
    {
        $columns = $db->describeColumns($table, $schema);
        $options = $db->tableOptions($table, $schema);
        $indexes = $db->describeIndexes($table, $schema);
        $references = $db->describeReferences($table, $schema);

        return new self([
            'name' => $table,
            'schema' => $schema,
            'columns' => array_map([ColumnDefinition::CLASS, 'fromColumn'], $columns),
            'indexes' => array_map([IndexDefinition::CLASS, 'fromIndex'], $indexes),
            'options' => $options,
            'references' => array_map([ReferenceDefinition::CLASS, 'fromReference'], $references),
        ]);
    }

    public function check()
    {
        $errors = new Message\Group;
        if (empty($this->columns)) {
            $errors->appendMessage(new Message("The table must contain at least one column", 'columns'));
        }
        $columns = [];
        foreach ($this->columns as $column) {
            $column->check();
            $columns[$column->name] = true;
        }
        if ($this->indexes) {
            foreach ($this->indexes as $index) {
                $index->check();
                foreach ($index->columns as $name) {
                    if (!isset($columns[$name])) {
                        $errors->appendMessage(new Message(
                            sprintf("The index '%s' column '%s' does not exist", $index->name, $name),
                            'indexes'
                        ));
                    }
                }
            }
        }
        if ($this->references) {
            foreach ($this->references as $reference) {
                $reference->check();
                foreach ($reference->columns as $name) {
                    if (!isset($columns[$name])) {
                        $errors->appendMessage(new Message(
                            sprintf("The index '%s' column '%s' does not exist", $index->name, $name),
                            'indexes'
                        ));
                    }
                }
            }
        }
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
    
    /**
     * @return array
     */
    public function toArray()
    {
        $def = parent::toArray();
        foreach (['columns', 'indexes', 'references'] as $field) {
            if ($this->$field) {
                $data = [];
                foreach ($this->$field as $item) {
                    $data[] = $item->toArray();
                }
                $def[$field] = $data;
            }
        }
        return array_filter($def);
    }

    public function getDefinition()
    {
        $def = [];
        foreach (['columns', 'indexes', 'references'] as $field) {
            if ($this->$field) {
                $data = [];
                foreach ($this->$field as $item) {
                    $data[$item->name] = $item->getDefinition();
                }
                $def[$field] = $data;
            }
        }
        if ($this->options) {
            $data = [];
            foreach ($this->options as $name => $val) {
                $data[] = $name . '=' . $val;
            }
            $def['options'] = implode(',', $data);
        }
        return $def;
    }

    public static function create($name, $definition)
    {
        $def = [];
        $parts = explode('.', $name);
        if (count($parts) > 1) {
            $def['name'] = $parts[1];
            $def['schema'] = $parts[0];
        } else {
            $def['name'] = $parts[0];
        }
        
        if (empty($definition['columns'])) {
            throw new Exception("The table must contain at least one column");
        }
        foreach (['columns' => ColumnDefinition::CLASS,
                  'indexes' => IndexDefinition::CLASS,
                  'references' => ReferenceDefinition::CLASS] as $field => $clz) {
            if (isset($definition[$field])) {
                $data = [];
                foreach ($definition[$field] as $name => $def) {
                    $data[] = $clz::create($name, $def);
                }
                $def[$field] = $data;
            }
        }
        if (isset($definition['options'])) {
            foreach (explode(',', $definition['options']) as $opt) {
                list($key, $val) = explode('=', $opt, 2);
                $options[$key] = $val;
            }
            $def['options'] = $options;
        }
        return new self($def);
    }
    
    /**
     * @return string
     */
    public function toSQL(AdapterInterface $db, $options = null)
    {
        if (!isset($options)) {
            $options = self::$DEFAULT_SQL_OPTIONS;
        }
        $this->check();
        $def['columns'] = $this->getColumnObjects();
        $def['indexes'] = $this->getIndexObjects();
        $def['options'] = $this->options;
        foreach ($options as $key => $val) {
            if (!$val) {
                unset($def['options'][$key]);
            }
        }
        $def['references'] = $this->getReferenceObjects();
        return $db->getDialect()->createTable($this->name, $this->schema, $def);
    }

    /**
     * @return TableDiff
     */
    public function compare(TableDefinition $table)
    {
        if ($this->name != $table->name
            || $this->schema != $table->schema) {
            throw new Exception("Table name is not the same");
        }
        $diff = new TableDiff(['name' => $this->name, 'schema' => $this->schema]);
        $diff->assign($this->diffColumns($table));
        $diff->assign($this->diffIndexes($table, $diff));
        $diff->assign($this->diffReferences($table, $diff));
        return $diff;
    }

    /**
     * @return TableDefinition
     */
    public function apply(TableDiff $diff)
    {
        if (!$diff->isChanged()) {
            return;
        }
        $this->applyColumnChanges($diff);
        $this->applyIndexChanges($diff);
        $this->applyReferenceChanges($diff);
    }
    
    public function getColumnObjects()
    {
        if ($this->columns) {
            return array_map(function ($col) {
                    return $col->toColumn();
            }, $this->columns);
        } else {
            throw new Exception("The table must contain at least one column");
        }
    }

    public function getIndexObjects()
    {
        if ($this->indexes) {
            return array_map(function ($index) {
                    return $index->toIndex();
            }, $this->indexes);
        }
        return [];
    }

    public function getReferenceObjects()
    {
        if ($this->references) {
            return array_map(function ($reference) {
                    return $reference->toReference();
            }, $this->references);
        }
        return [];
    }
    
    protected function diffColumns(TableDefinition $table)
    {
        $columns = [];
        foreach ($this->columns as $col) {
            $columns[$col->name] = $col;
        }
        $newColumns = [];
        $modifiedColumns = [];
        foreach ($table->columns as $col) {
            $name = $col->name;
            if (isset($columns[$name])) {
                if (!$columns[$name]->isSame($col)) {
                    $modifiedColumns[$name] = $col;
                }
                unset($columns[$name]);
            } else {
                $newColumns[$name] = $col;
            }
        }
        $dropedColumns = $columns;
        // check if rename
        $renamedColumns = [];
        foreach ($dropedColumns as $col) {
            foreach ($newColumns as $other) {
                if ($col->isSame($other)) {
                    unset($dropedColumns[$col->name]);
                    unset($newColumns[$other->name]);
                    $renamedColumns[$col->name] = $other;
                    break;
                }
            }
        }
        return [
            'dropedColumns' => $dropedColumns,
            'modifiedColumns' => $modifiedColumns,
            'renamedColumns' => $renamedColumns,
            'newColumns' => $newColumns
        ];
    }

    protected function diffIndexes(TableDefinition $table, TableDiff $diff)
    {
        if (!$this->indexes) {
            return ['newIndexes' => $table->indexes];
        }
        $indexes = [];
        foreach ($this->indexes as $index) {
            $indexes[$index->name] = $index;
        }
        $newIndexes = [];
        foreach ($table->indexes as $index) {
            if (isset($indexes[$index->name])) {
                if ($indexes[$index->name]->isSame($index, $diff->renamedColumns)) {
                    unset($indexes[$index->name]);
                } else {
                    $newIndexes[$index->name] = $index;
                }
            } else {
                $newIndexes[$index->name] = $index;
            }
        }
        return [
            'dropedIndexes' => $indexes,
            'newIndexes' => $newIndexes
        ];
    }

    protected function diffReferences(TableDefinition $table, TableDiff $diff)
    {
        if (!$this->references) {
            return ['newReferences' => $table->references];
        }
        $references = [];
        foreach ($this->references as $reference) {
            $references[$reference->name] = $reference;
        }
        $newReferences = [];
        foreach ($table->references as $reference) {
            if (isset($references[$reference->name])) {
                if ($references[$reference->name]->isSame($reference, $diff->renamedColumns)) {
                    unset($references[$reference->name]);
                } else {
                    $newReferences[$reference->name] = $reference;
                }
            } else {
                $newReferences[$reference->name] = $reference;
            }
        }
        return [
            'dropedReferences' => $references,
            'newReferences' => $newReferences
        ];
    }

    protected function applyColumnChanges(TableDiff $diff)
    {
    }
    
    protected function applyIndexChanges(TableDiff $diff)
    {
    }

    protected function applyReferenceChanges(TableDiff $diff)
    {
    }
}
