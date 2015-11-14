<?php
namespace PhalconX\Mvc\Middleware;

use Phalcon\Config;
use Phalcon\Events\Manager as EventsManager;
use PhalconX\Test\TestCase;
use PhalconX\Annotation\Annotations;
use PhalconX\Helper\ClassHelper;
use PhalconX\Test\Mvc\Middleware\ProController;

/**
 * TestCase for Filter
 */
class FilterProrityTest extends TestCase
{
    private $filter;

    private $eventsManager;

    private $registry;

    /**
     * @before
     */
    public function setupFilter()
    {
        $em = new EventsManager;
        $this->filter = new Filter(new Annotations(), $em);
        $em->attach('dispatch', $this->filter);
        $this->eventsManager = $em;
        $this->registry = new Config();
        $this->getDi()->setShared('registry', $this->registry);
    }

    public function testFilterMethodWin()
    {
        $dispatcher = $this->dispatcher;
        $dispatcher->setNamespaceName(ClassHelper::getNamespaceName(ProController::class));
        $dispatcher->setControllerName('pro');
        $dispatcher->setActionName('index');
        $ret = $this->eventsManager->fire('dispatch:beforeExecuteRoute', $dispatcher);
        $this->assertEquals($this->registry->orders, ['index']);
    }

    public function testFilterMethodDeclareOrder()
    {
        $dispatcher = $this->dispatcher;
        $dispatcher->setNamespaceName(ClassHelper::getNamespaceName(ProController::class));
        $dispatcher->setControllerName('pro');
        $dispatcher->setActionName('bar');
        $ret = $this->eventsManager->fire('dispatch:beforeExecuteRoute', $dispatcher);
        $this->assertEquals($this->registry->orders, ['bar', 'baz']);
    }

    public function testFilterMethodPrority()
    {
        $dispatcher = $this->dispatcher;
        $dispatcher->setNamespaceName(ClassHelper::getNamespaceName(ProController::class));
        $dispatcher->setControllerName('pro');
        $dispatcher->setActionName('baz');
        $ret = $this->eventsManager->fire('dispatch:beforeExecuteRoute', $dispatcher);
        $this->assertEquals($this->registry->orders, ['baz', 'bar']);
    }
}
