<?php
namespace PhalconX\Mvc;

use Phalcon\Http\Request;
use PhalconX\Annotation\Annotations;
use PhalconX\Test\TestCase;
use PhalconX\Test\Mvc\Router\IndexController;

/**
 * TestCase for Router
 */
class RouterTest extends TestCase
{
    private $router;
    
    /**
     * @before
     */
    public function setupRouter()
    {
        $di = $this->getDi();
        $di['request'] = $this->get(Request::class);
        $router = $this->get(Router::class, [['defaultRoutes' => false]]);
        $refl = new \ReflectionClass(IndexController::class);
        $router->scan(dirname($refl->getFilename()));
        $this->router = $router;
    }

    /**
     * @dataProvider uris
     */
    public function testHandle($method, $uri, $expect)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $router = $this->router;
        $router->handle($uri);
        if ($expect) {
            $this->assertEquals($expect, array_filter([
                'module' => $router->getModuleName(),
                'namespace' => $router->getNamespaceName(),
                'controller' => $router->getControllerName(),
                'action' => $router->getActionName(),
                'params' => $router->getParams(),
            ]), "route should match $method $uri");
        } else {
            $this->assertFalse($this->router->wasMatched(), "route should not match $method $uri");
        }
    }

    public function uris()
    {
        return [
            // UserController 没有默认匹配，必须对每个 action 设置 route
            ["GET", "/user", [
                'controller' => 'user',
                'action' => 'index',
                'namespace' => 'PhalconX\Test\Mvc\Router',
            ]],
            ["POST", "/user/edit/1", [
                'controller' => 'user',
                'action' => 'edit',
                'namespace' => 'PhalconX\Test\Mvc\Router',
                'params' => ['id' => "1"]
            ]],
            ["POST", "/user/edit/abc", null],
            ["GET", "/user/view/1", null],

            // AppController 设置默认匹配
            ["GET", "/app", [
                'controller' => 'app',
                'action' => 'index',
                'namespace' => 'PhalconX\Test\Mvc\Router',
            ]],
            ["GET", "/app/list", [
                'controller' => 'app',
                'action' => 'list',
                'namespace' => 'PhalconX\Test\Mvc\Router',
            ]],
            ["POST", "/app/edit/1", [
                'controller' => 'app',
                'action' => 'edit',
                'namespace' => 'PhalconX\Test\Mvc\Router',
                'params' => ["1"]
            ]],

            ["GET", "/", [
                'namespace' => 'PhalconX\Test\Mvc\Router',
                'controller' => 'index',
                'action' => 'index'
            ]]
        ];
    }

}
