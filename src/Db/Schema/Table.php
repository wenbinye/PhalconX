<?php
namespace PhalconX\Db\Schema;

use Phalcon\Db\AdapterInterface;
use Phalcon\Validation\Message;
use PhalconX\Exception\ValidationException;
use PhalconX\Validation\Annotations\IsArray;

class Table extends AbstractTable
{
    /**
     * @IsArray(Column)
     */
    public $columns = [];

    /**
     * @IsArray(Index)
     */
    public $indexes = [];

    /**
     * @IsArray(Reference)
     */
    public $references = [];

    /**
     * @var array table options
     */
    public $options = [];

    private static $DEFAULT_SQL_OPTIONS = [
        'auto_increment' => false,
    ];
    
    /**
     * Gets table description
     *
     * @return Table
     */
    public static function describeTable(AdapterInterface $db, $table, $schema = null)
    {
        $columns = $db->describeColumns($table, $schema);
        $indexes = $db->describeIndexes($table, $schema);
        $references = $db->describeReferences($table, $schema);
        $options = $db->tableOptions($table, $schema);

        return new self([
            'name' => $table,
            'schema' => $schema,
            'columns' => array_map([Column::class, 'fromColumn'], $columns),
            'indexes' => array_map([Index::class, 'fromIndex'], $indexes),
            'references' => array_map([Reference::class, 'fromReference'], $references),
            'options' => $options,
        ]);
    }

    public static function create($name, $definition)
    {
        $def = self::parseName($name);
        if (empty($definition['columns'])) {
            throw new \InvalidArgumentException("The table must contain at least one column");
        }
        foreach (['columns' => Column::class,
                  'indexes' => Index::class,
                  'references' => Reference::class] as $field => $clz) {
            if (isset($definition[$field])) {
                $data = [];
                foreach ($definition[$field] as $name => $item) {
                    $data[] = $clz::create($name, $item);
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
    public function compare(Table $table)
    {
        if ($this->name != $table->name
            || $this->schema != $table->schema) {
            throw new \InvalidArgumentException("Table name is not the same");
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
            throw new \InvalidArgumentException("The table must contain at least one column");
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
    
    protected function diffColumns(Table $table)
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
                if ($col->isLike($other)) {
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

    protected function diffIndexes(Table $table, TableDiff $diff)
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

    protected function diffReferences(Table $table, TableDiff $diff)
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
        $columns = [];
        foreach ($this->columns as $col) {
            $columns[$col->name] = $col;
        }
        if ($diff->dropedColumns) {
            foreach ($diff->dropedColumns as $col) {
                if (!isset($columns[$col->name])) {
                    throw new \InvalidArgumentException("The column '{$col->name}' was not precent in table " . $this->getName());
                }
                unset($columns[$col->name]);
            }
        }
        if ($diff->renamedColumns) {
            foreach ($diff->renamedColumns as $name => $col) {
                if (!isset($columns[$name])) {
                    throw new \InvalidArgumentException("The column '{$name}' was not precent in table " . $this->getName());
                }
            }
            unset($columns[$name]);
            $columns[$col->name] = $col;
        }
        if ($diff->newColumns) {
            foreach ($diff->newColumns as $col) {
                $columns[$col->name] = $col;
            }
        }
        $this->columns = array_values($columns);
    }
    
    protected function applyIndexChanges(TableDiff $diff)
    {
        $indexes = [];
        foreach ($this->indexes as $index) {
            $indexes[$index->name] = $index;
        }
        if ($diff->dropedIndexes) {
            foreach ($diff->dropedIndexes as $index) {
                unset($indexes[$index->name]);
            }
        }
        if ($diff->newIndexes) {
            foreach ($diff->newIndexes as $index) {
                $indexes[$index->name] = $index;
            }
        }
        $this->indexes = $indexes;
    }

    protected function applyReferenceChanges(TableDiff $diff)
    {
        $references = [];
        foreach ($this->references as $index) {
            $references[$index->name] = $index;
        }
        if ($diff->dropedReferences) {
            foreach ($diff->dropedReferences as $index) {
                unset($references[$index->name]);
            }
        }
        if ($diff->newReferences) {
            foreach ($diff->newReferences as $index) {
                $references[$index->name] = $index;
            }
        }
        $this->references = $references;
    }
}
