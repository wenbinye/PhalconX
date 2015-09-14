<?php
namespace PhalconX\Mvc\Router;

use Phalcon\Text;
use Phalcon\Mvc\Router;
use PhalconX\Util;
use PhalconX\Annotations\Mvc\Router\RoutePrefix;
use PhalconX\Annotations\Mvc\Router\Route;
use PhalconX\Annotations\ContextType;

class Annotations extends Router
{
    private $defaultAction;
    private $processed;
    private $handlers = [];
    private $controllerSuffix;
    private $actionSuffix;
    private $modelsMetadata;
    private $reflection;
    private $annotations;
    private $logger;

    public function __construct($options = null)
    {
        $this->defaultAction = Util::fetch($options, 'defaultAction', 'index');
        $this->controllerSuffix = Util::fetch($options, 'controllerSuffix', 'Controller');
        $this->actionSuffix = Util::fetch($options, 'actionSuffix', 'Action');
        $this->modelsMetadata = Util::service('modelsMetadata', $options, false);
        $this->reflection = Util::service('reflection', $options);
        $this->annotations = Util::service('annotations', $options);
        $this->logger = Util::service('logger', $options, false);
        parent::__construct(Util::fetch($options, 'defaultRoutes', true));
    }
    
    public function scan($dir, $module = null)
    {
        if ($this->modelsMetadata) {
            $handlers = $this->modelsMetadata->read('routes:' . $dir);
        }
        if (!isset($handlers)) {
            $handlers = [];
            foreach ($this->annotations->scan($dir, RoutePrefix::CLASS, ContextType::T_CLASS) as $annotation) {
                $handlers[] = [$annotation->value, $annotation->getClass(), $module];
            }
            $this->modelsMetadata->write('routes:' . $dir, $handlers);
        }
        $this->handlers = array_merge($this->handlers, $handlers);
        $this->processed = false;
    }

    public function addFileResource($file, $module = null)
    {
        foreach ($this->annotations->scanFile($file, RoutePrefix::CLASS, ContextType::T_CLASS) as $annotation) {
            $this->handlers[] = [$annotation->value, $annotation->getClass(), $module];
        }
        $this->processed = false;
    }
    
    public function addResource($handler, $prefix = null)
    {
        $this->handlers[] = [$prefix, $handler . $this->controllerSuffix, null];
        $this->processed = false;
    }

    public function addModuleResource($module, $handler, $prefix = null)
    {
        $this->handlers[] = [$prefix, $handler . $this->controllerSuffix, $module];
        $this->processed = false;
    }

    public function handle($uri = null)
    {
        if (!$uri) {
            $uri = $this->getRewriteUri();
        }
        if (!$this->processed) {
            foreach ($this->handlers as $scope) {
                list($prefix, $handler, $module) = $scope;
                if (!empty($prefix) && !Text::startsWith($uri, $prefix)) {
                    continue;
                }
                if ($this->controllerSuffix && !Text::endsWith($handler, $this->controllerSuffix)) {
                    if ($this->logger) {
                        $this->logger->error("Controller handler {$handler} not match suffix "
                                             . $this->controllerSuffix);
                    }
                    continue;
                }
                $this->processHandler($handler, $prefix, $module);
            }
            $this->processed = true;
        }
        return parent::handle($uri);
    }

    public function processHandler($handler, $prefix, $module)
    {
        if ($this->modelsMetadata) {
            $routes = $this->modelsMetadata->read($handler . ':routes');
        }
        if (isset($routes)) {
            $this->_routes = array_merge($this->_routes, $routes);
        } else {
            $routes = [];
            list($namespace, $class) = Util::splitClassName($handler);
            $context = [
                'module' => $module,
                'prefix' => $prefix,
                'namespace' => $namespace,
                'controller' => Text::uncamelize(substr($class, 0, -strlen($this->controllerSuffix))),
                'action' => null
            ];
            $collection = $this->annotations->get($handler, Route::CLASS);
            // make sure priority of class route less then method
            $annotations = $collection->classOnly()->merge($collection->methodsOnly());
            $methodRoutes = [];
            foreach ($annotations as $annotation) {
                if ($annotation->isClass()) {
                    $context['action'] = null;
                } else {
                    $method = $annotation->getMethod();
                    if ($this->actionSuffix && !Text::endsWith($method, $this->actionSuffix)) {
                        if ($this->logger) {
                            $this->logger->error(sprintf(
                                "Action %s::%s not match suffix %s",
                                $handler,
                                $method,
                                $this->actionSuffix
                            ));
                        }
                        continue;
                    }
                    if ($this->actionSuffix) {
                        $context['action'] = substr($method, 0, -strlen($this->actionSuffix));
                    } else {
                        $context['action'] = $annotation->getMethod();
                    }
                    $methodRoutes[$method] = true;
                }
                $routes[] = $this->processAnnotation($annotation, $context);
            }
            // 添加 defaultAction route
            $defaultAction = strtolower($this->defaultAction.$this->actionSuffix);
            if (method_exists($handler, $defaultAction)
                && !isset($methodRoutes[$defaultAction])) {
                $routes[] = $this->add($context["prefix"].'[/]?', [
                    'module' => $context['module'],
                    'namespace' => $context['namespace'],
                    'controller' => $context['controller'],
                    'action' => $this->defaultAction
                ]);
            }
            if ($this->logger) {
                $this->logger->info("parse routing annotation from $handler");
            }
            $this->modelsMetadata->write($handler.':routes', $routes);
        }
    }

    private function processAnnotation($annotation, $context)
    {
        $paths = $annotation->paths;
        foreach (['module', 'namespace', 'controller', 'action'] as $key) {
            if (isset($context[$key])) {
                $paths[$key] = $context[$key];
            }
        }
        $value = $annotation->value;
        if (isset($value)) {
            $value = ltrim($value, '/');
        } else {
            if ($this->defaultAction == $context['action']) {
                $value = '';
            } else {
                $value = $context['action'];
            }
        }
        $uri = rtrim($context['prefix'], '/');
        if (empty($value)) {
            $uri .= '[/]?';
        } else {
            $uri .= '/' . $value;
        }
        $route = $this->add($uri, $paths);
        $route->via($annotation->methods);
        if (is_array($annotation->converts)) {
            foreach ($annotation->converts as $param => $convert) {
                $route->convert($param, $convert);
            }
        }
        if ($annotation->beforeMatch) {
            $route->beforeMatch($annotation->beforeMatch);
        }
        if ($annotation->name) {
            $route->setName($annotation->name);
        }
        return $route;
    }

    public function getDefaultAction()
    {
        return $this->defaultAction;
    }

    public function setDefaultAction($defaultAction)
    {
        $this->defaultAction = $defaultAction;
        return $this;
    }

    public function getControllerSuffix()
    {
        return $this->controllerSuffix;
    }

    public function setControllerSuffix($controllerSuffix)
    {
        $this->controllerSuffix = $controllerSuffix;
        return $this;
    }

    public function getActionSuffix()
    {
        return $this->actionSuffix;
    }

    public function setActionSuffix($actionSuffix)
    {
        $this->actionSuffix = $actionSuffix;
        return $this;
    }
}
