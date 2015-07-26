<?php
namespace PhalconX\Mvc;

use Phalcon\Mvc\View;

class ControllerTest extends ControllerBaseTest
{
    public function testIndexAction()
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
        $this->response->setContent("hello");
    }
}
