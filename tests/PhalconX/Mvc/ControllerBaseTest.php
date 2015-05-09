<?php
namespace PhalconX\Mvc;

use Phalcon\Mvc\View;
use Phalcon\Events\Manager as EventsManager;

use PhalconX\Test\ControllerTestCase;

abstract class ControllerBaseTest extends ControllerTestCase
{
    function setUp()
    {
        $di = $this->getDI();
        $config = $di->getConfig();

        $view = new View;
        $view->disableLevel(array(
            View::LEVEL_LAYOUT => true,
            View::LEVEL_MAIN_LAYOUT => true
        ));
        $view->setViewsDir($config->fixturesDir.'/views/');
        $view->registerEngines(array(
            '.php' => "Phalcon\Mvc\View\Engine\Php"
        ));
        $di->set('view', $view);

        $dispatcher = $di->getDispatcher();
        $dispatcher->setDefaultNamespace('PhalconX\Mvc');

        $em = new EventsManager;
        $em->attach('dispatch:beforeException', function($event, $dispatcher, $exception) use($di) {
                // print_r($exception);
            });

    }
}
