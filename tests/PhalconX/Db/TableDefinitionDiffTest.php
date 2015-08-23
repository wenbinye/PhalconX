<?php
namespace PhalconX\Db;

use PhalconX\Util;
use Phalcon\Di;
use Phalcon\Db\Adapter\Pdo\Mysql;

/**
 * TestCase for TableDefinition
 */
class TableDefinitionDiffTest extends TableDefinitionBaseTest
{
    private $def;
    private $other;

    public function setUp()
    {
        $this->def = $this->objectMapper->map(
            file_get_contents($this->config->fixturesDir.'/table1.json'),
            TableDefinition::CLASS,
            'json'
        );
        $this->other = $this->objectMapper->map(
            $this->def->toArray(),
            TableDefinition::CLASS
        );
    }
    
    public function testDrop()
    {
        $other = $this->other;
        unset($other->columns[1]);
        unset($other->indexes[0]);
        // $other->check();
        $diff = $this->def->compare($other);
        // print_r($diff);
        $sql = $diff->toSQL(self::$db);
        $this->assertEquals($sql, 'ALTER TABLE `user` DROP COLUMN `name`;
ALTER TABLE `user` DROP INDEX `name`;');
    }

    public function testRename()
    {
        $other = $this->other;
        $other->columns[1]->name = 'username';
        $other->indexes[0]->columns = ['username'];
        $diff = $this->def->compare($other);
        $sql = $diff->toSQL(self::$db);
        $this->assertEquals($sql, 'ALTER TABLE `user` CHANGE `name` `username` VARCHAR(200) COMMENT "foo name" DEFAULT "2" NOT NULL');
    }

    public function testAddColumn()
    {
        $other = $this->other;
        $other->columns[] = new ColumnDefinition([
            'name' => 'gender',
            'type' => 'char',
            'size' => '1',
        ]);
        $diff = $this->def->compare($other);
        $sql = $diff->toSQL(self::$db);
        $this->assertEquals($sql, 'ALTER TABLE `user` ADD `gender` CHAR(1)');
    }
}
