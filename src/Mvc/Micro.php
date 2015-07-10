<?php
namespace PhalconX\Mvc;

use Phalcon\Mvc\Micro\Exception;
use Phalcon\Mvc\Micro\MiddlewareInterface;

class Micro extends \Phalcon\Mvc\Micro
{
    public function __construct(\Phalcon\DiInterface $di = null)
    {
        if ($di != null) {
            $this->setDI($di);
        }
    }

    public function handle($uri = null)
    {
        try {
            $returnedValue = $this->tryHandle($uri);
        } catch (\Exception $e) {
            $em = $this->eventsManager;
            if ($em) {
                $returnedValue = $em->fire('micro:beforeException', $this, $e);
            }
            if ($this->_errorHandler) {
                if (!is_callable($this->_errorHandler)) {
                    throw new Exception("Error handler is not callable");
                }
                $returnedValue = call_user_func($this->_errorHandler, $e);
            } else {
                $returnedValue = false;
            }
        }
        return $returnedValue;
    }

    private function tryHandle($uri)
    {
        $em = $this->eventsManager;
        if ($em) {
            if ($em->fire('micro:beforeHandleRoute', $this) === false) {
                return false;
            }
        }
        $router = $this->router;
        $router->handle($uri);
        if ($router->wasMatched()) {
            if ($em) {
                if ($em->fire('micro::beforeExecuteRoute', $this) === false) {
                    return false;
                }
            }
            $status = $this->callHandlers($this->_beforeHandlers, 'before');
            if ($status === false || $this->_stopped) {
                return $status;
            }
            $controllerClass = $router->getNamespaceName() . '\\'
                . ucfirst($router->getControllerName()) . 'Controller';
            $controller = $this->getSharedService($controllerClass);
            if (method_exists($controller, 'initialize')) {
                $controller->initialize();
            }
            $action = $router->getActionName();
            $returnedValue = call_user_func_array(array($controller, $action), $router->getParams());
            if ($em) {
                $em->fire('micro:afterExecuteRoute', $this);
            }
            $status = $this->callHandlers($this->_afterHandlers, 'after');
            if ($status === false || $this->_stopped) {
                return $status;
            }
        } else {
            if ($em) {
                if ($em->fire('micro:beforeNotFound', $this) === false) {
                    return false;
                }
            }
            if (!is_callable($this->_notFoundHandler)) {
                throw new Exception("Not-Found handler is not callable");
            }
            $returnedValue = call_user_func($this->_notFoundHandler, $this);
        }
        if ($em) {
            $em->fire('micro:afterHandleRoute', $this, $returnedValue);
        }
        $this->callHandlers($this->_finishHandlers, 'finish');
        return $returnedValue;
    }

    private function callHandlers($handlers, $type)
    {
        if (!is_array($handlers)) {
            return;
        }
        $this->_stopped = false;
        foreach ($handlers as $handler) {
            if (is_object($handler) && $handler instanceof MiddlewareInterface) {
                $status = $handler->call($this);
                if ($this->_stopped) {
                    break;
                }
                continue;
            }
            if (!is_callable($handler)) {
                throw new Exception("'$type' handler is not callable");
            }
            if (call_user_func($handler, $this) === false) {
                return false;
            }
            if ($this->_stopped) {
                return $status;
            }
        }
        return $this->_stopped ? $status : true;
    }
}
