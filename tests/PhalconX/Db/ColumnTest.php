<?php
namespace PhalconX\Db;

use PhalconX\Test\TestCase;

/**
 * TestCase for Column
 */
class ColumnTest extends TestCase
{
    /**
     * @before
     */
    public function setupTable()
    {
        $this->db->execute($this->dataset('tables/scope.sql'));
    }

    public function testCopy()
    {
        $columns = $this->db->describeColumns('scopes');
        // print_r($columns);
        $column = Column::copy($columns[0]);
        // print_r($column);
        $this->assertTrue($column instanceof Column);
        $this->assertEquals($column->getSize(), 50);
        $this->assertEquals($column->toArray(), [
            'name' => 'name',
            'type' => 2,
            'typeReference' => -1,
            'isNumeric' => false,
            'size' => 50,
            'unsigned' => false,
            'notNull' => false,
            'primary' => false,
            'autoIncrement' => false,
            'first' => true,
            'bindType' => 2,
        ]);
    }
}
