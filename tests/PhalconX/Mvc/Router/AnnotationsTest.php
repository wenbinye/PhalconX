<?php
namespace PhalconX\Mvc\Router;

use PhalconX\Test\TestCase;

/**
 * TestCase for Annotations
 */
class AnnotationsTest extends TestCase
{
    private function prepare()
    {
        $router = $this->getDi()->get(Annotations::CLASS);
        $router->clear();
        $router->scan($this->config->testBaseDir.'/PhalconX/Test/Controllers');
        $router->setDefaultNamespace('PhalconX\Test\Controllers');
        return $router;
    }
    
    public function testScan()
    {
        foreach ($this->uriData() as $case) {
            $router = $this->prepare();
            list($method, $uri, $result) = $case;
            $msg = "route test $method $uri";
            $_SERVER['REQUEST_METHOD'] = $method;
            $router->handle($uri);
            if ($result) {
                $this->assertEquals($result, array_filter([
                    'module' => $router->getModuleName(),
                    'namespace' => $router->getNamespaceName(),
                    'controller' => $router->getControllerName(),
                    'action' => $router->getActionName(),
                    'params' => $router->getParams(),
                ]), $msg);
            } else {
                $this->assertFalse($router->wasMatched(), $msg);
            }
        }
    }

    public function uriData()
    {
        return [
            ["GET", "/user", [
                'controller' => 'user',
                'action' => 'index',
                'namespace' => 'PhalconX\Test\Controllers',
            ]],
            ["POST", "/user/edit/1", [
                'controller' => 'user',
                'action' => 'edit',
                'namespace' => 'PhalconX\Test\Controllers',
                'params' => ['id' => "1"]
            ]],
            ["POST", "/user/edit/abc", null],

            ["GET", "/app", [
                'controller' => 'app',
                'action' => 'index',
                'namespace' => 'PhalconX\Test\Controllers',
            ]],
            ["POST", "/app/edit/1", [
                'controller' => 'app',
                'action' => 'edit',
                'namespace' => 'PhalconX\Test\Controllers',
                'params' => ['id' => "1"]
            ]],

            ["GET", "/", [
                'namespace' => 'PhalconX\Test\Controllers',
                'controller' => 'index',
                'action' => 'index'
            ]]
        ];
    }
}
