<?php
namespace PhalconX\Test\Controllers;

/**
 * @RoutePrefix("/user")
 */
class UserController
{
    /**
     * @Get("")
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
