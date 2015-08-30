<?php
namespace PhalconX\Db;

use Phalcon\Db\Reference;
use Phalcon\Validation\Message;
use PhalconX\Mvc\SimpleModel;
use PhalconX\Exception\ValidationException;
use PhalconX\Exception;

class ReferenceDefinition extends SimpleModel
{
    public $name;
    public $referencedTable;
    public $columns;
    public $referencedColumns;
    public $schema;
    public $referencedSchema;
    public $onDelete;
    public $onUpdate;

    public static function fromReference(Reference $reference)
    {
        return new self([
            'name' => $ref->getName(),
            'referencedTable' => $ref->getReferencedTable(),
            'columns' => $ref->getColumns(),
            'referencedColumns' => $ref->getReferencedColumns(),
            'schema' => $ref->getSchemaName(),
            'referencedSchema' => $ref->getReferencedSchema(),
            'onDelete' => $ref->getOnDelete(),
            'onUpdate' => $ref->getOnUpdate()
        ]);
    }

    public function toReference()
    {
        return new Reference($this->name, $this->toArray());
    }

    public function check()
    {
        $errors = new Message\Group;
        foreach (['name', 'columns', 'referenceColumns', 'referenceTable'] as $field) {
            if (!$this->$field) {
                $errors->appendMessage(new Message("Reference $field is required", $field));
            }
        }
        
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    public function isSame(ReferenceDefinition $other)
    {
        if ($renamedColumns) {
            $columns = [];
            foreach ($this->columns as $col) {
                $columns[] = isset($renamedColumns[$col]) ? $renamedColumns[$col]->name : $col;
            }
        } else {
            $columns = $this->columns;
        }
        return $columns == $other->columns
            && $this->referencedTable == $other->referencedTable
            && $this->referencedColumns == $other->referencedColumns
            && $this->schema == $other->schema
            && $this->referencedSchema == $other->referencedSchema
            && $this->onDelete == $other->onDelete
            && $this->onUpdate == $other->onUpdate;
    }

    public function getDefinition()
    {
        $def = sprintf(
            '(%s) REFERENCES %s (%s)',
            implode(',', $this->columns),
            ($this->referencedSchema ? $this->referencedSchema . '.' : '') . $this->referencedTable,
            implode(',', $this->referencedColumns)
        );
        $other = [];
        if ($this->onDelete) {
            $other['onDelete'] = $this->onDelete;
        }
        if ($this->onUpdate) {
            $other['onUpdate'] = $this->onUpdate;
        }
        if ($other) {
            $def .= ' ' . json_encode($other, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
        }
        return $def;
    }

    public function create($name, $definition)
    {
        $def = [];
        $pos = strpos($definition, ' {');
        if ($pos !== false) {
            $def = json_decode(substr($definition, $pos+1), true);
            $definition = substr($definition, 0, $pos);
        }
        if (preg_match('/\((.*?)\) REFERENCES (\S+) \((.*)\)/', $definition, $matches)) {
            $def['columns'] = explode(',', $matches[1]);
            $parts = explode('.', $matches[2]);
            if (count($parts) == 1) {
                $def['referencedTable'] = $parts[0];
            } else {
                $def['referencedSchema'] = $parts[0];
                $def['referencedTable'] = $parts[1];
            }
            $def['referencedColumns'] = explode(',', $matches[3]);
            $def['name'] = $name;
            return new self($def);
        } else {
            throw new Exception("Invalid reference definition '$definition'");
        }
    }
}
