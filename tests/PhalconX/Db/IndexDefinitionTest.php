<?php
namespace PhalconX\Db;

use PhalconX\Test\TestCase;

/**
 * TestCase for IndexDefinition
 */
class IndexDefinitionTest extends TestCase
{
    public function testGetDefinition()
    {
        $index = new IndexDefinition(['name' => 'PRIMARY', 'columns' => ['id']]);
        $this->assertEquals($index->getDefinition(), 'PRIMARY KEY(id)');
    }

    public function testCreate()
    {
        $index = IndexDefinition::create('PRIMARY', 'PRIMARY KEY(id)');
        // print_r($index);
        $this->assertEquals($index->name, 'PRIMARY');
        $this->assertEquals($index->columns, ['id']);
    }

    public function testGetDefinitionUniq()
    {
        $index = new IndexDefinition(['name' => 'name', 'type' => 'UNIQUE', 'columns' => ['name', 'age']]);
        $this->assertEquals($index->getDefinition(), 'UNIQUE KEY(name,age)');
    }

    public function testCreateUniq()
    {
        $index = IndexDefinition::create('name', 'UNIQUE KEY(name,age)');
        // print_r($index);
        $this->assertEquals($index->name, 'name');
        $this->assertEquals($index->type, 'UNIQUE');
        $this->assertEquals($index->columns, ['name', 'age']);
    }

    public function testGetDefinitionKey()
    {
        $index = new IndexDefinition(['name' => 'name', 'columns' => ['name', 'age']]);
        $this->assertEquals($index->getDefinition(), 'KEY(name,age)');
    }

    public function testCreateKey()
    {
        $index = IndexDefinition::create('name', 'KEY(name,age)');
        // print_r($index);
        $this->assertEquals($index->name, 'name');
        $this->assertEquals($index->type, '');
        $this->assertEquals($index->columns, ['name', 'age']);
    }
}
