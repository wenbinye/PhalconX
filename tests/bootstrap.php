<?php
use Phalcon\DI\FactoryDefault;

use Phalcon\Logger\Adapter\Stream as ConsoleLogger;

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
        'fixturesDir' => __DIR__ . '/fixtures'
    ));
    $di->setShared('config', $config);

    $di->setShared('logger', new ConsoleLogger('php://stderr'));
}
bootstrap_test();

