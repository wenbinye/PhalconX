<?php
namespace PhalconX\Mvc;

use Phalcon\DI\Injectable;

class SwaggerApplication extends Injectable
{
    public function __construct(\Phalcon\DiInterface $di = null)
    {
        if ($di != null) {
            $this->setDI($di);
        }
    }

    public function handle($uri = null)
    {
        $router = $this->router;
        $router->handle($uri);
        if ($router->wasMatched()) {
            $controller = $router->getNamespaceName() . '\\' . ucfirst($router->getControllerName()) . 'Controller';
            $action = $router->getActionName();
            return call_user_func_array(array($this->getDI()->get($controller), $action), $router->getParams());
        } else {
            return false;
        }
    }
}
