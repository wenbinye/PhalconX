<?php
namespace PhalconX\Db\Schema;

use Phalcon\Di;
use Phalcon\Db\Adapter\Pdo\Mysql;
use PhalconX\Test\TestCase;
use PhalconX\Db\DbHelper;

/**
 * TestCase for TableDefinition
 */
class TableTest extends TestCase
{
    private $db;
    /**
     * @before
     */
    public function setupTable()
    {
        $this->db = DbHelper::getConnection($this->config->mysql->toArray());
        $this->db->execute($this->dataset('db/user.sql'));
    }
    
    public function testCreate()
    {
        $schema = $this->config->mysql->dbname;
        $def = Table::describeTable($this->db, 'user', $schema);
        $arr = $def->toArray();

        $sql = $def->toSql($this->db, ['auto_increment' => false]);
        // var_export([$sql, $arr]);
        $this->assertEquals($sql, "CREATE TABLE `$schema`.`user` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(200) COMMENT \"user name\" DEFAULT \"2\" NOT NULL,
	`age` INT(11),
	`status` TINYINT(4),
	`address_id` INT(11),
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	UNIQUE KEY `status` (`status`, `address_id`),
	KEY `age` (`age`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
        $arr = $def->getDefinition();
        $this->assertEquals($arr, array (
            'columns' => array (
                'id' => 'integer(11)=int isNumeric notNull autoIncrement first',
                'name' => 'varchar(200) notNull after=id {"default":"2","comment":"user name"}',
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
