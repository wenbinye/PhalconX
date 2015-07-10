<?php
namespace PhalconX\Mvc;

class ControllerForwardTest extends ControllerBaseTest
{
    function testForward()
    {
        $_GET['_url'] = '/forward1/';
        $response = $this->getResponse();
        $this->assertEquals('yes', $response);
    }
}

class Forward1Controller extends Controller
{
    public function indexAction()
    {
        $this->forward('forward2/index');
    }
}

class Forward2Controller extends Controller
{
    public function indexAction()
    {
        echo 'yes';
    }
}
