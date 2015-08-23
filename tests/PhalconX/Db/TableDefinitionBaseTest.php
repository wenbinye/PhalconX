<?php
namespace PhalconX\Db;

use PhalconX\Util;
use Phalcon\Di;
use PhalconX\Test\TestCase;
use Phalcon\Db\Adapter\Pdo\Mysql;

/**
 * TestCase for TableDefinition
 */
class TableDefinitionBaseTest extends TestCase
{
    protected static $db;
    
    public static function setUpBeforeClass()
    {
        $db = new Mysql([
            'adapter'     => 'Mysql',
            'host'        => 'localhost',
            'username'    => 'test',
            'password'    => 'test',
            'dbname'      => 'test',
            'charset'     => 'utf8',
        ]);
        self::$db = Util::mixin($db, new DbHelper);
    }
}
