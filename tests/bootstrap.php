<?php
use Phalcon\DI\FactoryDefault;

use Phalcon\Cache;
use Phalcon\Logger\Adapter\Stream as ConsoleLogger;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use PhalconX\Mvc\Metadata\Memory as MetadataAdapter;
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

    $di = new FactoryDefault();
    $config = new \Phalcon\Config(array(
        'fixturesDir' => __DIR__ . '/fixtures',
        'testBaseDir' => __DIR__
    ));
    $di['config'] = $config;
    $di['cache'] = function () {
        $frontend = new Cache\Frontend\None;
        $backend = new Cache\Backend\Memory($frontend);
        return $backend;
    };
    $di['db'] = function () use ($config) {
        Model::setup(['notNullValidations' => false]);
        $db = new Sqlite(['dbname' => ':memory:']);
        if (!$db->tableExists('scopes')) {
            $db->execute(file_get_contents($config->fixturesDir . '/schema.sql'));
        }
        return $db;
    };
    
    $di['modelsMetadata'] = MetadataAdapter::CLASS;
    $di['reflection'] = 'PhalconX\Util\Reflection';
    $di['objectMapper'] = 'PhalconX\Util\ObjectMapper';
    $di['validator'] = 'PhalconX\Validator';
    $di['annotations'] = 'PhalconX\Annotations';

    // Changing the logger format
    $formatter = new LineFormatter("%date% [%type%] %message%\n");
    $logger = new ConsoleLogger('php://stderr');
    $logger->setFormatter($formatter);
    $di['logger'] = $logger;
    return $di;
}
return bootstrap_test();
