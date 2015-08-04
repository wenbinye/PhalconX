<?php
namespace PhalconX\Cli;

use PhalconX\Exception;
use PhalconX\Di\Injectable;
use PhalconX\Cli\Tasks\HelpTask;

class Application
{
    use Injectable;

    private $errorHandler;
    private $notFoundHandler;
    private $beforeHandlers;
    private $afterHandlers;
    private $finishHandlers;
    private $stopped;
    
    public function __construct(\Phalcon\DiInterface $di = null)
    {
        if ($di != null) {
            $this->setDi($di);
        }
    }

    public function handle($arguments = null)
    {
        try {
            $returnedValue = $this->tryHandle($arguments);
        } catch (\Exception $e) {
            $em = $this->eventsManager;
            if ($em) {
                if ($em->fire('dispatch:beforeException', $this, $e) === false) {
                    return false;
                }
            }
            if ($this->errorHandler) {
                if (!is_callable($this->errorHandler)) {
                    throw new Exception("Error handler is not callable");
                }
                $returnedValue = call_user_func($this->errorHandler, $e);
            } else {
                throw $e;
            }
        }
        return $returnedValue;
    }

    private function tryHandle($arguments)
    {
        $em = $this->eventsManager;
        if ($em) {
            if ($em->fire('dispatch:beforeHandleRoute', $this) === false) {
                return false;
            }
        }
        $router = $this->router;
        $router->scan(__DIR__.'/Tasks', 'cli');
        $router->handle($arguments);
        if ($router->wasMatched()) {
            if ($em) {
                if ($em->fire('dispatch::beforeExecuteRoute', $this) === false) {
                    return false;
                }
            }
            $status = $this->callHandlers($this->beforeHandlers, 'before');
            if ($status === false || $this->stopped) {
                return $status;
            }
            $taskClass = $router->getNamespaceName() . '\\' . $router->getTaskName();
            $task = $this->getDi()->get($taskClass, [$router->getParams()]);
            if (method_exists($task, 'initialize')) {
                $task->initialize();
            }
            $returnedValue = call_user_func(
                array($task, $router->getActionName()),
                (object) $router->getParams()
            );
            if ($em) {
                $em->fire('dispatch:afterExecuteRoute', $this);
            }
            $status = $this->callHandlers($this->afterHandlers, 'after');
            if ($status === false || $this->stopped) {
                return $status;
            }
        } else {
            if ($em) {
                if ($em->fire('dispatch:beforeNotFound', $this) === false) {
                    return false;
                }
            }
            if (!is_callable($this->notFoundHandler)) {
                $this->defaultNotFound();
            }
            $returnedValue = call_user_func($this->notFoundHandler, $this);
        }
        if ($em) {
            $em->fire('dispatch:afterHandleRoute', $this, $returnedValue);
        }
        $this->callHandlers($this->finishHandlers, 'finish');
        return $returnedValue;
    }

    private function defaultNotFound()
    {
        global $argv;
        $arguments = $argv;
        echo "Task not found\n\n";
        array_splice($arguments, 1, 0, ['cli:help']);
        $this->handle($arguments);
        exit(-1);
    }
    
    private function callHandlers($handlers, $type)
    {
        if (!is_array($handlers)) {
            return;
        }
        $this->stopped = false;
        foreach ($handlers as $handler) {
            if (is_object($handler) && $handler instanceof MiddlewareInterface) {
                $status = $handler->call($this);
                if ($this->stopped) {
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
            if ($this->stopped) {
                return $status;
            }
        }
        return $this->stopped ? $status : true;
    }

    public function error($errorHandler)
    {
        $this->errorHandler = $errorHandler;
        return $this;
    }

    public function notFound($notFoundHandler)
    {
        $this->notFoundHandler = $notFoundHandler;
        return $this;
    }

    public function before($handler)
    {
        $this->beforeHandlers[] = $handler;
        return $this;
    }

    public function after($handler)
    {
        $this->afterHandlers[] = $handler;
        return $this;
    }

    public function finish($handler)
    {
        $this->finishHandlers[] = $handler;
        return $this;
    }
}
