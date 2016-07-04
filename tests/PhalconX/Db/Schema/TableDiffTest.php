<?php
namespace PhalconX\Db\Schema;

use Phalcon\Di;
use PhalconX\Test\TestCase;
use PhalconX\Db\DbHelper;

/**
 * TestCase for TableDefinition
 */
class TableDiffTest extends TestCase
{
    private $def;

    private $db;

    /**
     * @before
     */
    public function setupTable()
    {
        $this->db = DbHelper::getConnection($this->config->mysql->toArray());
        $this->db->execute($this->dataset('db/user.sql'));

        $this->def = Table::describeTable($this->db, 'user', $this->config->mysql->dbname);
    }

    public function changes()
    {
        $db = "`" . Di::getDefault()->getConfig()->mysql->dbname . "`";
        return [
            [
                'ALTER TABLE `user` CHANGE `name` `username` VARCHAR(200) COMMENT "foo name" DEFAULT "2" NOT NULL',
                "ALTER TABLE $db.`user` CHANGE `name` `username` VARCHAR(200) COMMENT \"foo name\" DEFAULT \"2\" NOT NULL",
            ],
            [
                'alter table user drop column `name`',
                "ALTER TABLE $db.`user` DROP INDEX `name`;
ALTER TABLE $db.`user` DROP COLUMN `name`"],
            [
                'ALTER TABLE `user` ADD `gender` CHAR(1)',
                "ALTER TABLE $db.`user` ADD `gender` CHAR(1)"
            ]
        ];
    }

    /**
     * @dataProvider changes
     */
    public function testDiff($sql, $diffSql)
    {
        $this->db->execute($sql);
        $other = Table::describeTable($this->db, 'user', $this->config->mysql->dbname);
        $diff = $this->def->compare($other);
        $sql = $diff->toSQL($this->db);
        $this->assertEquals($sql, $diffSql);
    }
}
