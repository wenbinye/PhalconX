<?php
namespace PhalconX\Test\Controllers;

/**
 * @RoutePrefix("/app")
 */
class AppController
{
    /**
     * @Get("/")
     */
    public function indexAction()
    {
    }

    /**
     * @GET
     */
    public function listAction()
    {
    }

    /**
     * @POST("edit/{id}")
     */
    public function editAction($id)
    {
    }
}
