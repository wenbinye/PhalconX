<?php
namespace PhalconX\Mvc\Middleware;

use Phalcon\Config;
use Phalcon\Events\Manager as EventsManager;
use PhalconX\Test\TestCase;
use PhalconX\Annotation\Annotations;
use PhalconX\Helper\ClassHelper;
use PhalconX\Test\Mvc\Middleware\MyController;
use PhalconX\Test\Mvc\Middleware\Foo;
use PhalconX\Exception\HttpException;

/**
 * TestCase for Filter
 */
class FilterTest extends TestCase
{
    private $filter;

    private $eventsManager;

    private $registry;

    /**
     * @before
     */ 
    public function setupFilter()
    {
        $di = $this->getDi();
        $this->filter = $di->get(Filter::class);

        $em = new EventsManager;
        $this->eventsManager = $em;
        $em->attach('dispatch', $this->filter);
        $di['eventsManager'] = $em;

        $this->registry = new Config();
        $di['registry'] = $this->registry;
        $di['annotations'] = Annotations::class;
    }

    public function testFilterOk()
    {
        $this->registry->filterExpect = 'true';
        $dispatcher = $this->dispatcher;
        $dispatcher->setNamespaceName(ClassHelper::getNamespaceName(MyController::class));
        $dispatcher->setControllerName('my');
        $dispatcher->setActionName('index');
        $ret = $this->eventsManager->fire('dispatch:beforeExecuteRoute', $dispatcher);
        $this->assertNull($ret);
        $this->assertTrue($this->registry->filterResult);
    }

    public function testFilterFalse()
    {
        $this->registry->filterExpect = 'false';
        $dispatcher = $this->dispatcher;
        $dispatcher->setNamespaceName(ClassHelper::getNamespaceName(MyController::class));
        $dispatcher->setControllerName('my');
        $dispatcher->setActionName('index');
        $ret = $this->eventsManager->fire('dispatch:beforeExecuteRoute', $dispatcher);
        $this->assertFalse($ret);
        $this->assertFalse($this->registry->filterResult);
    }

    public function testFilterException()
    {
        $this->registry->filterExpect = 'exception';
        $dispatcher = $this->dispatcher;
        $dispatcher->setNamespaceName(ClassHelper::getNamespaceName(MyController::class));
        $dispatcher->setControllerName('my');
        $dispatcher->setActionName('index');
        try {
            $ret = $this->eventsManager->fire('dispatch:beforeExecuteRoute', $dispatcher);
        } catch (HttpException $e) {
            $this->assertEquals($e->getStatusCode(), 405);
            $trace = $e->getPrevious()->getTrace();
            $this->assertEquals($trace[0]['class'], Foo::class);
        }
    }
}
