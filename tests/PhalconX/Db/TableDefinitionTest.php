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
        $def = TableDefinition::describeTable(self::$db, 'user');
        $arr = $def->toArray();
        $sql = $def->toSql(self::$db, ['auto_increment' => false]);
        // var_export([$sql, $arr]);
        $this->assertEquals($sql, 'CREATE TABLE `user` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(200) COMMENT "foo name" DEFAULT "2" NOT NULL,
	`age` INT(11),
	`status` TINYINT(4),
	`address_id` INT(11),
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	UNIQUE KEY `status` (`status`, `address_id`),
	KEY `age` (`age`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci');
        $arr = $def->getDefinition();
        $this->assertEquals($arr, array (
            'columns' => array (
                'id' => 'integer(11)=int isNumeric notNull autoIncrement first',
                'name' => 'varchar(200) notNull after=id {"default":"2","comment":"foo name"}',
                'age' => 'integer(11)=int isNumeric after=name',
                'status' => 'integer(4)=int isNumeric after=age',
                'address_id' => 'integer(11)=int isNumeric after=status',
            ),
            'indexes' => array (
                'PRIMARY' => 'PRIMARY KEY(id)',
                'name' => 'UNIQUE KEY(name)',
                'status' => 'UNIQUE KEY(status,address_id)',
                'age' => 'KEY(age,status)',
            ),
            'options' => 'table_type=BASE TABLE,auto_increment=1,engine=InnoDB,table_collation=utf8_general_ci',
        ));
    }
}
