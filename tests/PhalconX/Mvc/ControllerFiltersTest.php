<?php
namespace PhalconX\Mvc;

use PhalconX\Mvc\ControllerBaseTest;
use PhalconX\Mvc\Controller\Filters;

class FiltersTest extends ControllerBaseTest
{
    public function setUp()
    {
        parent::setUp();
        $di = $this->getDi();
        $em = $di->getDispatcher()->getEventsManager();
        $em->attach('dispatch', $di->get(Filters::CLASS));
    }
    
    public function testFilter()
    {
        $_GET['_url'] = '/filter/index';
        $response = $this->getResponse();
        print_r($this->response->getHeaders());
        print_r($response);
    }
}

class FilterController extends Controller
{
    /**
     * @Json
     */
    public function indexAction()
    {
        $this->response->setJsonContent(['success' => true]);
    }
}

