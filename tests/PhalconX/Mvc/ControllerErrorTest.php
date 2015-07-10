<?php
namespace PhalconX\Mvc;

class ControllerErrorTest extends ControllerBaseTest
{
    public function testError()
    {
        $_GET['_url'] = '/error/trigger';
        $response = $this->getResponse();
        // var_export($response);
        $this->assertEquals('error catched', $response);
    }
}

class ErrorController extends Controller
{
    protected function initialize()
    {
        parent::initialize();
        $this->defaultActions['error'] = 'error/index';
    }

    public function indexAction()
    {
        echo 'error catched';
    }

    public function triggerAction()
    {
        $val = $_GET['no such key'];
    }
}
