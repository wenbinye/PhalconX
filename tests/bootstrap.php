<?php
use Phalcon\Di\FactoryDefault;
use Phalcon\Config;
use Phalcon\Logger\Adapter\Stream as ConsoleLogger;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Mvc\Model;
use Phalcon\Db\Adapter\Pdo\Sqlite;

define("FIXTURES_DIR", __DIR__."/fixtures");

function bootstrap_test()
{
    $autoload = __DIR__."/../vendor/autoload.php";
    if (!file_exists($autoload)) {
        chdir(__DIR__ . "/../");
        system("composer dumpautoload") == 0
            or die("Cannot create autoload. Please download composer from https://getcomposer.org/");
    }
    $loader = require($autoload);
    $loader->add('PhalconX', array(__DIR__));
    if (file_exists(__DIR__.'/.env')) {
        \Dotenv::load(__DIR__);
    }

    $di = new FactoryDefault();
    $di['config'] = $config = new Config([
        'fixturesDir' => __DIR__ . '/fixtures',
        'testBaseDir' => __DIR__,
        'mysql' => [
            'adapter' => 'mysql',
            'host' => isset($_SERVER['DB_HOST']) ? $_SERVER['DB_HOST'] : '127.0.0.1',
            'username' => isset($_SERVER['DB_USER']) ? $_SERVER['DB_USER'] : 'root',
            'password' => isset($_SERVER['DB_PASS']) ? $_SERVER['DB_PASS'] : '',
            'dbname' => 'test',
        ]
    ]);
    $di['db'] = function () {
        Model::setup(['notNullValidations' => false]);
        $db = new Sqlite(['dbname' => ':memory:']);
        return $db;
    };
    $di['logger'] = function () {
        // Changing the logger format
        $formatter = new LineFormatter("%date% [%type%] %message%\n");
        $logger = new ConsoleLogger('php://stderr');
        $logger->setFormatter($formatter);
        return $logger;
    };
    return $di;
}

bootstrap_test();
