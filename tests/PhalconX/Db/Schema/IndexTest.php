<?php
namespace PhalconX\Db\Schema;

use PhalconX\Test\TestCase;

/**
 * TestCase for IndexDefinition
 */
class IndexTest extends TestCase
{
    /**
     * @dataProvider indexes
     */
    public function testGetDefinition($data, $def, $name)
    {
        $index = new Index($data);
        $this->assertEquals($index->getDefinition(), $def);
    }

    /**
     * @dataProvider indexes
     */
    public function testCreate($data, $def, $name, $type)
    {
        $index = Index::create($name, $def);
        // print_r($index);
        $this->assertEquals($index->name, $name);
        $this->assertEquals($index->type, $type);
        $this->assertEquals($index->columns, $data['columns']);
    }

    public function indexes()
    {
        return [
            [
                ['name' => 'PRIMARY', 'columns' => ['id']],
                'PRIMARY KEY(id)', 'PRIMARY', 'PRIMARY'
            ],
            [
                ['name' => 'name', 'type' => 'UNIQUE', 'columns' => ['name', 'age']],
                'UNIQUE KEY(name,age)', 'name', 'UNIQUE'
            ],
            [
                ['name' => 'name', 'columns' => ['name', 'age']],
                'KEY(name,age)', 'name', ''
            ]
        ];
    }
}
