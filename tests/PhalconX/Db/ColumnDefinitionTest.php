<?php
namespace PhalconX\Db;

use PhalconX\Test\TestCase;
use PhalconX\Db\Dialect\Mysql;

/**
 * TestCase for ColumnDefinition
 */
class ColumnDefinitionTest extends TestCase
{
    /**
     * @dataProvider columns
     */
    public function testGetDefinition($columns, $def)
    {
        $column = new ColumnDefinition($columns);
        // var_export($column->toArray());
        $this->assertEquals($column->getDefinition(), $def);
    }

    /**
     * @dataProvider columns
     */
    public function testCreate($columns, $def)
    {
        $column = ColumnDefinition::create($columns['name'], $def);
        $this->assertEquals($column->toArray(), $columns);
    }

    public function columns()
    {
        return [
            [
                array(
                    'name' => 'id',
                    'type' => 'integer',
                    'size' => 11,
                    'isNumeric' => true,
                    'notNull' => true,
                    'primary' => true,
                    'autoIncrement' => true,
                    'first' => true,
                    'bindType' => 'int',
                ),
                'integer(11)=int isNumeric notNull primary autoIncrement first',
            ],
            [
                array (
                    'name' => 'created_at',
                    'type' => 'datetime',
                    'notNull' => true,
                    'after' => 'id',
                ),
                'datetime notNull after=id'
            ],
        ];
    }

    public function testToColumn()
    {
        $col = new ColumnDefinition([
            'name' => 'releases',
            'type' => 'integer',
            'size' => 11,
            'isNumeric' => true,
            'notNull' => true,
            'bindType' => 'int',
            'default' => '0'
        ]);
        $obj = $col->toColumn();
        $dialect = new Mysql();
        $sql = $dialect->modifyColumn('app', null, $obj, null);
        $this->assertEquals($sql, 'ALTER TABLE `app` MODIFY `releases` INT(11) DEFAULT "0" NOT NULL');
    }
}
