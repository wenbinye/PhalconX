<?php
namespace PhalconX\Test\Mvc\Router;

use PhalconX\Mvc\Annotations\Route\RoutePrefix;
use PhalconX\Mvc\Annotations\Route\Route;
use PhalconX\Mvc\Annotations\Route\Post;

/**
 * @RoutePrefix("/user")
 */
class UserController
{
    /**
     * @Get
     */
    public function indexAction()
    {
    }

    /**
     * @Get
     */
    public function listAction()
    {
    }

    public function viewAction($id)
    {
    }

    /**
     * @Post("edit/{id:[0-9]+}")
     */
    public function editAction($id)
    {
    }
}
