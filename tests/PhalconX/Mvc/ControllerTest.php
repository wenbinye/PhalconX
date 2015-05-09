<?php
namespace PhalconX\Mvc;

use Phalcon\Mvc\View;

class ControllerTest extends ControllerBaseTest
{
    function createController()
    {
        return $this->getDI()->get('PhalconX\Mvc\TestController');
    }
    
    function testIndexAction()
    {
        $_GET['_url'] = '/test/index';
        $response = $this->getResponse();
        $this->assertEquals('hello', $response);
    }
}

class TestController extends Controller
{
    public function indexAction()
    {
        echo "hello";
    }
}
