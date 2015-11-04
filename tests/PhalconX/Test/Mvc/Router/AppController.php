<?php
namespace PhalconX\Test\Mvc\Router;

use PhalconX\Mvc\Annotations\Route\RoutePrefix;
use PhalconX\Mvc\Annotations\Route\Route;

/**
 * @RoutePrefix("/app")
 * @Route(":action/:params", paths=[action=1, params=2])
 * @Route(":action", paths=[action=1])
 */
class AppController
{
    public function indexAction()
    {
    }

    public function listAction()
    {
    }

    public function editAction($id)
    {
    }
}
