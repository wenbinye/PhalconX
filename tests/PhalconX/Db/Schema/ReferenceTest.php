<?php
namespace PhalconX\Db\Schema;

use PhalconX\Test\TestCase;

/**
 * TestCase for ReferenceDefinition
 */
class ReferenceTest extends TestCase
{
    /**
     * @dataProvider references
     */
    public function testGetDefinition($data, $def, $name)
    {
        $ref = new Reference($data);
        $this->assertEquals($ref->getDefinition(), $def);
        
    }

    /**
     * @dataProvider references
     */
    public function testCreate($data, $def, $name)
    {
        $ref = Reference::create($name, $def);
        $this->assertEquals(array_filter($ref->toArray()), $data);
    }

    public function references()
    {
        return [
            [
                [
                    'name' => 'address_id',
                    'referencedTable' => 'address',
                    'columns' => ['address_id'],
                    'referencedColumns' => ['id'],
                    'referencedSchema' => 'other',
                    'onDelete' => 'delete from a',
                    'onUpdate' => 'update a'
                ],
                '(address_id) REFERENCES other.address (id) {"onDelete":"delete from a","onUpdate":"update a"}',
                'address_id'
            ]
        ];
    }
}
