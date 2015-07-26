<?php
namespace PhalconX\Test\Controllers;

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
