<?php
namespace PhalconX\Test\Mvc\Router;

use PhalconX\Mvc\Annotations\Route\RoutePrefix;
use PhalconX\Mvc\Annotations\Route\Get;

/**
 * @RoutePrefix
 */
class IndexController
{
    /**
     * @Get
     */
    public function indexAction()
    {
    }
}
