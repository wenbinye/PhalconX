<?php
namespace PhalconX\Mvc;

use Phalcon\Mvc\View;

class ControllerRenderTest extends ControllerBaseTest
{
    public function testRenderAction()
    {
        $_GET['_url'] = '/render/';
        $response = $this->getResponse();
        $this->assertEquals('render test', $response);
    }

    public function testRenderVarsAction()
    {
        $_GET['_url'] = '/render/vars';
        $response = $this->getResponse();
        $this->assertEquals('render for hello', $response);
    }

    public function testRenderActionAction()
    {
        $_GET['_url'] = '/render/action';
        $response = $this->getResponse();
        $this->assertEquals('render for action', $response);
    }

    public function testRenderFull()
    {
        $_GET['_url'] = '/render/full';
        $response = $this->getResponse();
        $this->assertEquals('render for full', $response);
    }

    public function testRenderReturn()
    {
        $_GET['_url'] = '/render/return';
        $response = $this->getResponse();
        $this->assertEquals('msg = render for return', $response);
    }
}

class RenderController extends Controller
{
    public function indexAction()
    {
        $this->render();
    }

    public function varsAction()
    {
        $this->render(array(
            'msg' => 'hello'
        ));
    }

    public function actionAction()
    {
        $this->render('vars', array(
            'msg' => 'action'
        ));
    }

    public function fullAction()
    {
        $this->render('other/vars', array(
            'msg' => 'full'
        ));
    }

    public function returnAction()
    {
        $msg = $this->render('other/vars', array('msg' => 'return'), true);
        $this->response->setContent("msg = $msg");
    }
}
