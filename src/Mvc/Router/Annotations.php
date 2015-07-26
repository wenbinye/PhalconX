<?php
namespace PhalconX\Mvc\Router;

use Phalcon\Text;
use Phalcon\Mvc\Router;
use PhalconX\Util;

class Annotations extends Router
{
    const ROUTE_PREFIX = 'RoutePrefix';
    const ROUTE = 'ROUTE';

    private $defaultAction;
    private $processed;
    private $handlers = [];
    private $controllerSuffix;
    private $actionSuffix;
    private $modelsMetadata;
    private $reflection;
    private $annotations;
    private $logger;

    private static $METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];

    public function __construct($options = null)
    {
        $this->defaultAction = Util::fetch($options, 'defaultAction', 'index');
        $this->controllerSuffix = Util::fetch($options, 'controllerSuffix', 'Controller');
        $this->actionSuffix = Util::fetch($options, 'actionSuffix', 'Action');
        $this->modelsMetadata = Util::service('modelsMetadata', $options, false);
        $this->reflection = Util::service('reflection', $options);
        $this->annotations = Util::service('annotations', $options);
        $this->logger = Util::service('logger', $options, false);
    }
    
    public function scan($dir, $module = null)
    {
        if ($this->modelsMetadata) {
            $handlers = $this->modelsMetadata->read('routes:' . $dir);
        }
        if (!isset($handlers)) {
            $backup = $this->handlers;
            $this->handlers = [];
            $self = $this;
            Util::walkdir($dir, function ($file) use ($self, $module) {
                $self->addFileResource($file, $module);
            });
            if ($this->logger) {
                $this->logger->info("scan routing resources from $dir");
            }
            $handlers = $this->handlers;
            $this->handlers = $backup;
            $this->modelsMetadata->write('routes:' . $dir, $handlers);
        }
        $this->handlers = array_merge($this->handlers, $handlers);
    }

    public function addFileResource($file, $module = null)
    {
        $classes = $this->reflection->getClasses($file);
        foreach ($classes as $class) {
            if (!Text::endsWith($class, $this->controllerSuffix)) {
                continue;
            }
            $handlerAnnotations = $this->annotations->get($class);
            if (!$handlerAnnotations) {
                continue;
            }
            $annotations = $handlerAnnotations->getClassAnnotations();
            if (!$annotations) {
                continue;
            }
            foreach ($annotations as $annotation) {
                if ($annotation->getName() == self::ROUTE_PREFIX) {
                    $handler = substr($class, 0, -strlen($this->controllerSuffix));
                    $this->addModuleResource($module, $handler, $annotation->getArgument(0));
                }
            }
        }
    }
    
    public function addResource($handler, $prefix = null)
    {
        $this->handlers[] = [$prefix, $handler, null];
        $this->processed = false;
    }

    public function addModuleResource($module, $handler, $prefix = null)
    {
        $this->handlers[] = [$prefix, $handler, $module];
        $this->processed = false;
    }

    public function handle($uri = null)
    {
        if (!$uri) {
            $uri = $this->getRewriteUri();
        }
        if (!$this->processed) {
            foreach ($this->handlers as $scope) {
                $prefix = $scope[0];
                if (!empty($prefix) && !Text::startsWith($uri, $prefix)) {
                    continue;
                }
                $this->processHandler($scope[1], $prefix, $scope[2]);
            }
            $this->processed = true;
        }
        return parent::handle($uri);
    }

    public function processHandler($handler, $prefix, $module)
    {
        $handlerClass = $handler . $this->controllerSuffix;
        if ($this->modelsMetadata) {
            $routes = $this->modelsMetadata->read($handlerClass . ':routes');
        }
        if (!isset($routes)) {
            $backup = $this->_routes;
            $this->_routes = [];
            $handlerAnnotations = $this->annotations->get($handlerClass);
            if ($handlerAnnotations) {
                list($namespace, $class) = $this->splitClassName($handlerClass);
                $context = [
                    'module' => $module,
                    'prefix' => $prefix,
                    'namespace' => $namespace,
                    'controller' => Text::uncamelize(substr($class, 0, -strlen($this->controllerSuffix))),
                    'action' => null
                ];
                $annotations = $handlerAnnotations->getClassAnnotations();
                if ($annotations) {
                    foreach ($annotations as $annotation) {
                        $this->processAnnotation($annotation, $context);
                    }
                }
                $methodAnnotations = $handlerAnnotations->getMethodsAnnotations();
                $methodRoutes = [];
                if ($methodAnnotations) {
                    foreach ($methodAnnotations as $method => $collection) {
                        if (!Text::endsWith($method, $this->actionSuffix)) {
                            continue;
                        }
                        $context['action'] = substr($method, 0, -strlen($this->actionSuffix));
                        if ($collection) {
                            foreach ($collection as $annotation) {
                                $methodRoutes[strtolower($method)] =  $this->processAnnotation($annotation, $context);
                            }
                        }
                    }
                }
                $defaultAction = strtolower($this->defaultAction.$this->actionSuffix);
                if (method_exists($handlerClass, $defaultAction)
                    && !isset($methodRoutes[$defaultAction])) {
                    $this->add($context["prefix"].'[/]?', [
                        'module' => $context['module'],
                        'namespace' => $context['namespace'],
                        'controller' => $context['controller'],
                        'action' => $this->defaultAction
                    ]);
                }
            }
            $routes = $this->_routes;
            $this->_routes = $backup;
            if ($this->logger) {
                $this->logger->info("parse routing annotation from $handlerClass");
            }
            $this->modelsMetadata->write($handlerClass.':routes', $routes);
        }
        $this->_routes = array_merge($this->_routes, $routes);
    }

    private function processAnnotation($annotation, $context)
    {
        $name = strtoupper($annotation->getName());
        if ($name != self::ROUTE && !in_array($name, self::$METHODS)) {
            return;
        }
        $paths = $annotation->getNamedArgument('paths');
        if (!is_array($paths)) {
            $paths = [];
        }
        foreach (['module', 'namespace', 'controller', 'action'] as $key) {
            if (isset($context[$key])) {
                $paths[$key] = $context[$key];
            }
        }
        $value = $annotation->getArgument(0);
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
        if ($name != self::ROUTE) {
            $route->via($name);
        } else {
            $methods = $annotation->getNamedArgument('methods');
            if ($methods) {
                $route->via($methods);
            }
        }
        $converts = $annotation->getNamedArgument('converts');
        if (is_array($converts)) {
            foreach ($converts as $param => $convert) {
                $route->convert($param, $convert);
            }
        }
        $beforeMatch = $annotation->getNamedArgument('beforeMatch');
        if ($beforeMatch) {
            $route->beforeMatch($beforeMatch);
        }
        $routeName = $annotation->getNamedArgument('name');
        if ($routeName) {
            $route->setName($routeName);
        }
        return $route;
    }
    
    private function splitClassName($class)
    {
        $pos = strrpos($class, '\\');
        if ($pos !== false) {
            return [substr($class, 0, $pos), substr($class, $pos+1)];
        } else {
            return [null, $class];
        }
    }
}
