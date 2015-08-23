<?php
namespace PhalconX\Db;

use Phalcon\Db\AdapterInterface;

class TableDiff extends BaseTable
{
    /**
     * @var ColumnDefinition[]
     */
    public $dropedColumns;

    /**
     * @var ColumnDefinition[]
     */
    public $newColumns;

    /**
     * @var Map<String, ColumnDefinition>
     */
    public $renamedColumns;

    /**
     * @var ColumnDefinition[]
     */
    public $modifiedColumns;

    /**
     * @var IndexDefinition[]
     */
    public $dropedIndexes;

    /**
     * @var IndexDefinition[]
     */
    public $newIndexes;

    /**
     * @var ReferenceDefinition[]
     */
    public $dropedReferences;

    /**
     * @var ReferenceDefinition[]
     */
    public $newReferences;

    public function toSQL(AdapterInterface $db)
    {
        $sql = [];
        $dialect = $db->getDialect();
        if ($this->dropedColumns) {
            foreach ($this->dropedColumns as $col) {
                $sql[] = $dialect->dropColumn($this->name, $this->schema, $col->name);
            }
        }
        if ($this->modifiedColumns) {
            foreach ($this->modifiedColumns as $col) {
                $sql[] = $dialect->modifyColumn($this->name, $this->schema, $col->toColumn());
            }
        }
        if ($this->renamedColumns) {
            foreach ($this->renamedColumns as $name => $col) {
                $colObj = $col->toColumn();
                $col->name = $name;
                $oldColObj = $col->toColumn();
                $sql[] = $dialect->modifyColumn($this->name, $this->schema, $colObj, $oldColObj);
            }
        }
        if ($this->newColumns) {
            foreach ($this->newColumns as $col) {
                $sql[] = $dialect->addColumn($this->name, $this->schema, $col->toColumn());
            }
        }
        if ($this->dropedIndexes) {
            foreach ($this->dropedIndexes as $index) {
                if ($index->isPrimaryKey()) {
                    $sql[] = $dialect->dropPrimaryKey($this->name, $this->schema);
                } else {
                    $sql[] = $dialect->dropIndex($this->name, $this->schema, $index->name);
                }
            }
        }
        if ($this->newIndexes) {
            foreach ($this->newIndexes as $index) {
                if ($index->isPrimaryKey()) {
                    $sql[] = $dialect->addPrimaryKey($this->name, $this->schema, $index->toIndex());
                } else {
                    $sql[] = $dialect->addIndex($this->name, $this->schema, $index->toIndex());
                }
            }
        }
        if ($this->dropedReferences) {
            foreach ($this->dropedReferences as $reference) {
                $sql[] = $dialect->dropForeignKey($this->table, $this->schema, $reference->name);
            }
        }
        if ($this->newReferences) {
            foreach ($this->newReferences as $reference) {
                $sql[] = $dialect->addForeignKey($this->table, $this->schema, $reference->toReference());
            }
        }
        return implode(";\n", $sql);
    }

    public function isChanged()
    {
        $vars = $this->toArray();
        unset($vars['name']);
        unset($vars['schema']);
        return !empty(array_filter($vars));
    }
}
