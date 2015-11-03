<?php
namespace PhalconX\Test\Model;

use Phalcon\Di;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use PhalconX\Di\FactoryDefault;

class Bootstrap
{
    private static $di;
    
    public static function setUp()
    {
        self::$di = Di::getDefault();
        Di::reset();
        $di = new FactoryDefault();
        $di['db'] = new Sqlite(["dbname" => ":memory:"]);
        $di['db']->execute(file_get_contents(FIXTURES_DIR.'/tables/scope.sql'));
    }

    public static function tearDown()
    {
        Di::setDefault(self::$di);
    }
}
