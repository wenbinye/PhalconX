<?php
namespace PhalconX\Test\Mvc\Middleware;

/**
 * @Bar(name=class)
 */
class ProController
{
    /**
     * @Bar(name=index, priority=1000)
     */
    public function indexAction()
    {
    }

    /**
     * @Bar(name=bar)
     * @Baz(name=baz)
     */
    public function barAction()
    {
    }

    /**
     * @Bar(name=bar, priority=10)
     * @Baz(name=baz, priority=9)
     */
    public function bazAction()
    {
    }
}
