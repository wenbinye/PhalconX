<?php
use Phalcon\DI\FactoryDefault;

use Phalcon\Cache;
use Phalcon\Logger\Adapter\Stream as ConsoleLogger;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Mvc\Model\Metadata\Memory as MetadataAdapter;

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
    $di->setShared('cache', function () {
            $frontend = new Cache\Frontend\None;
            $backend = new Cache\Backend\Memory($frontend);
            return $backend;
    });
    $di->setShared('modelsMetadata', function () {
            return new MetadataAdapter();
    });


    // Changing the logger format
    $formatter = new LineFormatter("%date% [%type%] %message%\n");
    $logger = new ConsoleLogger('php://stderr');
    $logger->setFormatter($formatter);
    $di->setShared('logger', $logger);
}
bootstrap_test();
