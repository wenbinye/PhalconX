<?php
namespace PhalconX\Db;

use PhalconX\Util;
use Phalcon\Di;
use Phalcon\Db\Adapter\Pdo\Mysql;

/**
 * TestCase for TableDefinition
 */
class TableDefinitionTest extends TableDefinitionBaseTest
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$db->dropTable('user');
        self::$db->execute(file_get_contents(Di::getDefault()->getConfig()->fixturesDir.'/table1.sql'));
    }
    
    public function testCreate()
    {
        $def = TableDefinition::create(self::$db, 'user');
        $arr = $def->toArray();
        $sql = $def->toSql(self::$db, ['auto_increment' => false]);
        // var_export([$sql, $arr]);
        $this->assertEquals($sql, 'CREATE TABLE `user` (
	`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`name` VARCHAR(200) COMMENT "foo name" DEFAULT "2" NOT NULL,
	`age` INT(11),
	`status` TINYINT(4),
	`address_id` INT(11),
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	UNIQUE KEY `status` (`status`, `address_id`),
	KEY `age` (`age`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci');
        $arr = $def->toArray();
        echo json_encode($arr);
    }
}
