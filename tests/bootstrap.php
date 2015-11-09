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
    \Dotenv::load(__DIR__);

    $di = new FactoryDefault();
    $di['config'] = $config = new Config([
        'fixturesDir' => __DIR__ . '/fixtures',
        'testBaseDir' => __DIR__,
        'mysql' => [
            'host' => $_ENV['DB_HOST'],
            'username' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASS'],
            'dbname' => 'test',
            'dsn' => [
                'charset' => 'utf-8'
            ]
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
