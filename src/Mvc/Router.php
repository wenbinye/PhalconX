<?php
namespace PhalconX\Mvc;

use Phalcon\Text;
use Phalcon\Cache;
use Phalcon\Mvc\Router as BaseRouter;
use PhalconX\Annotation\Annotations;
use PhalconX\Mvc\Annotations\Route\RoutePrefix;
use PhalconX\Mvc\Annotations\Route\Route;
use PhalconX\Helper\ArrayHelper;
use PhalconX\Helper\ClassHelper;

class Router extends BaseRouter
{
    /**
     * @var boolean whether handlers is processed
     */
    private $processed;

    /**
     * @var array cached handlers
     */
    private $handlers = [];

    /**
     * @var string default action
     */
    private $defaultAction;

    /**
     * @var string controller suffix
     */
    private $controllerSuffix;

    /**
     * @var string action suffix
     */
    private $actionSuffix;

    /**
     * @var Annotations
     */
    private $annotations;

    /**
     * @var Cache\BackendInterface
     */
    private $cache;

    /**
     * @var \PhalconX\Logger\AdapterInterface logger
     */
    private $logger;

    public function __construct(array $options = null)
    {
        $this->defaultAction = ArrayHelper::fetch($options, 'defaultAction', 'index');
        $this->controllerSuffix = ArrayHelper::fetch($options, 'controllerSuffix', 'Controller');
        $this->actionSuffix = ArrayHelper::fetch($options, 'actionSuffix', 'Action');
        parent::__construct(ArrayHelper::fetch($options, 'defaultRoutes', true));
    }
    
    public function scan($dir, $module = null)
    {
        $handlers = $this->getCache()->get('_PHX.route_controllers.' . $dir);
        if (!isset($handlers)) {
            $handlers = [];
            $it = $this->getAnnotations()->scan($dir)
                ->is(RoutePrefix::class)
                ->onClass();
            foreach ($it as $annotation) {
                $handlers[] = [$annotation->value, $annotation->getClass(), $module];
            }
            $this->getCache()->save('_PHX.route_controllers.' . $dir, $handlers);
        }
        $this->handlers = array_merge($this->handlers, $handlers);
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
                    $this->getLogger()->error("Controller handler {$handler} not match suffix "
                                             . $this->controllerSuffix);
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
        $routes = $this->getCache()->get('_PHX.routes.'.$handler);
        if (isset($routes)) {
            $this->_routes = array_merge($this->_routes, $routes);
        } else {
            $routes = [];
            list($namespace, $class) = ClassHelper::splitName($handler);
            $context = [
                'module' => $module,
                'prefix' => $prefix,
                'namespace' => $namespace,
                'controller' => Text::uncamelize(substr($class, 0, -strlen($this->controllerSuffix))),
                'action' => null
            ];
            $it = $this->getAnnotations()->iterate($handler)
                ->is(Route::class)
                ->onClassOrMethods();
            $methodRoutes = [];
            foreach ($it as $annotation) {
                if ($annotation->isOnClass()) {
                    $context['action'] = null;
                } else {
                    $method = $annotation->getMethodName();
                    if ($this->actionSuffix && !Text::endsWith($method, $this->actionSuffix)) {
                        $this->getLogger()->warning("Invalid route annotation " . $annotation);
                        continue;
                    }
                    
                    if ($this->actionSuffix) {
                        $context['action'] = substr($method, 0, -strlen($this->actionSuffix));
                    } else {
                        $context['action'] = $annotation->getMethodName();
                    }
                    $methodRoutes[strtolower($method)] = true;
                }
                $routes[] = $this->processAnnotation($annotation, $context);
            }
            $default = strtolower($this->defaultAction . $this->actionSuffix);
            if (!isset($methodRoutes[$default]) && method_exists($handler, $default)) {
                $routes[] = $this->add($context["prefix"].'[/]?', [
                    'module' => $context['module'],
                    'namespace' => $context['namespace'],
                    'controller' => $context['controller'],
                    'action' => $this->defaultAction
                ]);
            }
            $this->getCache()->save('_PHX.routes.'.$handler, $routes);
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

        // normalize pattern
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
        if (is_array($annotation->converters)) {
            foreach ($annotation->converters as $param => $convert) {
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

    public function setDefaultAction($action)
    {
        $this->defaultAction = $action;
        return $this;
    }

    public function getControllerSuffix()
    {
        return $this->controllerSuffix;
    }

    public function setControllerSuffix($suffix)
    {
        $this->controllerSuffix = $suffix;
        return $this;
    }

    public function getActionSuffix()
    {
        return $this->actionSuffix;
    }

    public function setActionSuffix($suffix)
    {
        $this->actionSuffix = $suffix;
        return $this;
    }

    /**
     * @return logger
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = $this->getAnnotations()->getLogger();
        }
        return $this->logger;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Gets the annotations component
     *
     * @return Annotations
     */
    public function getAnnotations()
    {
        if ($this->annotations === null) {
            $this->annotations = $this->getDi()->getAnnotations();
        }
        return $this->annotations;
    }

    public function setAnnotations(Annotations $annotations)
    {
        $this->annotations = $annotations;
        return $this;
    }

    /**
     * Gets the cache component
     *
     * @return \Phalcon\Cache\BackendInterface
     */
    public function getCache()
    {
        if ($this->cache === null) {
            $this->cache = $this->getAnnotations()->getCache();
        }
        return $this->cache;
    }

    public function setCache(Cache\BackendInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }
}
