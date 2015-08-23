<?php
namespace PhalconX\Db;

use PhalconX\Test\TestCase;

/**
 * TestCase for ReferenceDefinition
 */
class ReferenceDefinitionTest extends TestCase
{
    public function testGetDefinition()
    {
        $ref = new ReferenceDefinition([
            'name' => 'address_id',
            'referencedTable' => 'address',
            'columns' => ['address_id'],
            'referencedColumns' => ['id'],
            'referencedSchema' => 'other',
            'onDelete' => 'delete from a',
            'onUpdate' => 'update a'
        ]);
        $this->assertEquals($ref->getDefinition(), '(address_id) REFERENCES other.address (id) {"onDelete":"delete from a","onUpdate":"update a"}');
        
    }

    public function testCreate()
    {
        $ref = ReferenceDefinition::create('address_id', '(address_id) REFERENCES other.address (id) {"onDelete":"delete from a","onUpdate":"update a"}');
        // var_export($ref->toArray());
        $this->assertEquals($ref->toArray(), array (
            'name' => 'address_id',
            'referencedTable' => 'address',
            'columns' => ['address_id'],
            'referencedColumns' => ['id'],
            'schema' => NULL,
            'referencedSchema' => 'other',
            'onDelete' => 'delete from a',
            'onUpdate' => 'update a',
        ));
    }
}
