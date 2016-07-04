<?php
namespace PhalconX;

use Closure;
use BadMethodCallException;
use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Phalcon\Di as PhalconDi;
use Phalcon\DiInterface as PhalconDiInterface;
use Phalcon\Di\ServiceInterface;
use Phalcon\Di\Exception;
use Phalcon\Text;

use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Model\MetaData\Memory as ModelsMetadata;
use Phalcon\Filter;
use Phalcon\Escaper;
use Phalcon\Annotations\Adapter\Memory as Annotations;
use Phalcon\Security;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
use Phalcon\Cli\Router as CliRouter;
use Phalcon\Cli\Dispatcher as CliDispatcher;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Http\Response;
use Phalcon\Http\Response\Cookies;
use Phalcon\Http\Request;
use Phalcon\Crypt;
use Phalcon\Flash\Direct as Flash;
use Phalcon\Flash\Session as FlashSession;
use Phalcon\Tag;
use Phalcon\Session\Adapter\Files as Session;
use Phalcon\Session\Bag as SessionBag;
use Phalcon\Assets\Manager as AssetsManager;

use PhalconX\Di\DefinitionInterface;
use PhalconX\Di\Definition\Helper\DefinitionHelperInterface;
use PhalconX\Di\Definition\Resolver\DefinitionResolverInterface;
use PhalconX\Di\Definition\Resolver\ObjectDefinitionResolver;
use PhalconX\Di\Definition\FactoryDefinition;
use PhalconX\Di\Definition\ArrayDefinition;
use PhalconX\Di\Definition\ValueDefinition;
use PhalconX\Di\Definition\ObjectDefinition;
use PhalconX\Di\Scope;
use PhalconX\Di\DeferredObject;
use PhalconX\Di\DiInterface;
use PhalconX\Di\Container;

class Di extends PhalconDi implements DiInterface
{
    /**
     * @var array<DefinitionInterface>
     */
    private $definitions = [];

    /**
     * @var DefinitionResolverInterface
     */
    private $definitionResolver;

    /**
     * @var array
     */
    private $singletonEntries = [];

    /**
     * @var array
     */
    private $requestEntries = [];

    /**
     * @var bool
     */
    private $freshInstance;

    public function __construct($addContainer = true, $addPhalcon = true)
    {
        parent::__construct();
        $this->definitionResolver = new ObjectDefinitionResolver;
        if ($addContainer) {
            $this->addContainerDefinitions();
        }
        if ($addPhalcon) {
            $this->addPhalconDefinitions();
        }
    }

    protected function normalize($name)
    {
        return ltrim($name, '\\');
    }

    protected function getDefinition($name)
    {
        if (isset($this->definitions[$name])) {
            $definition = $this->definitions[$name];
        } elseif ($this->definitionResolver->isResolvable($name)) {
            $definition = $this->definitionResolver->resolve($name);
            $this->definitions[$name] = $definition;
        } else {
            throw new Exception("No entry or class found for '$name'");
        }
        return $definition;
    }

    protected function createDefinition($name, $definition, $defaultScope)
    {
        if ($definition instanceof Closure) {
            $definition = new FactoryDefinition($name, $definition);
        } elseif (is_array($definition)) {
            $definition = new ArrayDefinition($name, $definition);
        } elseif ($definition instanceof DefinitionHelperInterface) {
            $definition = $definition->getDefinition($name);
        } elseif (!($definition instanceof DefinitionInterface)) {
            $definition = new ValueDefinition($name, $definition);
        }
        if ($definition->getScope() === null) {
            $definition->setScope($defaultScope);
        }
        return $definition;
    }

    public function setDefinitionResolver(DefinitionResolverInterface $definitionResolver)
    {
        $this->definitionResolver = $definitionResolver;
        return $this;
    }

    public function addDefinitions(array $definitions)
    {
        foreach ($definitions as $name => $definition) {
            $this->set($name, $definition);
        }
    }

    public function addContainerDefinitions()
    {
        $this->addDefinitions([
            DiInterface::class => new ValueDefinition(DiInterface::class, $this),
            PhalconDiInterface::class => new ValueDefinition(PhalconDiInterface::class, $this),
            ContainerInterface::class => new ValueDefinition(ContainerInterface::class, new Container($this))
        ]);
    }

    public function addPhalconDefinitions($mode = null)
    {
        if ($mode === null) {
            $mode = PHP_SAPI;
        }
        $this->addDefinitions([
            'modelsManager' => new ObjectDefinition('modelsManager', ModelsManager::class),
            'modelsMetadata' => new ObjectDefinition('modelsMetadata', ModelsMetadata::class),
            'filter' => new ObjectDefinition('filter', Filter::class),
            'escaper' => new ObjectDefinition('escaper', Escaper::class),
            'annotations' => new ObjectDefinition('annotations', Annotations::class),
            'security' => new ObjectDefinition('security', Security::class),
            'eventsManager' => new ObjectDefinition('eventsManager', EventsManager::class),
            'transactionManager' => new ObjectDefinition('transactionManager', TransactionManager::class),
        ]);
        if ($mode === 'cli') {
            $this->addDefinitions([
                'router' => new ObjectDefinition('router', CliRouter::class),
                'dispatcher' => new ObjectDefinition('dispatcher', CliDispatcher::class)
            ]);
        } else {
            $this->addDefinitions([
                'router' => new ObjectDefinition('router', Router::class),
                'dispatcher' => new ObjectDefinition('dispatcher', Dispatcher::class),
                'url' => new ObjectDefinition('url', UrlResolver::class),
                'response' => new ObjectDefinition('response', Response::class),
                'request' => new ObjectDefinition('request', Request::class),
                'cookies' => new ObjectDefinition('cookies', Cookies::class),
                'crypt' => new ObjectDefinition('crypt', Crypt::class),
                'flash' => new ObjectDefinition('flash', Flash::class),
                'flashSession' => new ObjectDefinition('flashSession', FlashSession::class),
                'tag' => new ObjectDefinition('tag', Tag::class),
                'session' => new ObjectDefinition('session', Session::class),
                'sessionBag' => new ObjectDefinition('sessionBag', SessionBag::class),
                'assets' => new ObjectDefinition('assets', AssetsManager::class)
            ]);
        }
    }

    public function __call($method, $args = null)
    {
        if (Text::startsWith($method, "get")) {
            $name = lcfirst(substr($method, 3));
            if (isset($this->definitions[$name])) {
                if ($args) {
                    return $this->get($name, $args);
                } else {
                    return $this->get($name);
                }
            }
        }
        throw new BadMethodCallException("Unknown method '$method'");
    }
    
    /**
     * @inheritDoc
     */
    public function set($name, $definition, $shared = false)
    {
        $name = $this->normalize($name);
        return $this->definitions[$name] = $this->createDefinition($name, $definition, Scope::PROTOTYPE);
    }

    /**
     * @inheritDoc
     */
    public function setShared($name, $definition)
    {
        $name = $this->normalize($name);
        return $this->definitions[$name] = $this->createDefinition($name, $definition, Scope::SINGLETON);
    }

    /**
     * @inheritDoc
     */
    public function remove($name)
    {
        $name = $this->normalize($name);
        unset($this->definitions[$name]);
        unset($this->singletonEntries[$name]);
        unset($this->requestEntries[$name]);
    }

    /**
     * @inheritDoc
     */
    public function attempt($name, $definition, $shared = false)
    {
        $name = $this->normalize($name);
        if (!array_key_exists($name, $this->definitions)) {
            return $this->set($name, $definition, $shared);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get($name, $parameters = null)
    {
        $name = $this->normalize($name);
        $definition = $this->getDefinition($name);
        if (!($definition instanceof DefinitionInterface)) {
            throw new Exception("Invalid definition for '$name'");
        }
        $scope = $definition->getScope();
        if ($scope === Scope::SINGLETON && array_key_exists($name, $this->singletonEntries)) {
            return $this->singletonEntries[$name];
        } elseif ($scope === Scope::REQUEST && array_key_exists($name, $this->requestEntries)) {
            return $this->requestEntries[$name];
        }
        $value = $definition->resolve($parameters, $this);
        if ($value instanceof DeferredObject) {
            $deferred = $value;
            $value = $deferred->getInstance();
        }
        if ($scope === Scope::SINGLETON) {
            $this->singletonEntries[$name] = $value;
        } elseif ($scope === Scope::REQUEST) {
            $this->requestEntries[$name] = $value;
        }
        if (isset($deferred)) {
            $deferred->initialize();
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getShared($name, $parameters = null)
    {
        $name = $this->normalize($name);
        if (array_key_exists($name, $this->singletonEntries)) {
            $this->freshInstance = true;
            return $this->singletonEntries[$name];
        } elseif (array_key_exists($name, $this->requestEntries)) {
            $this->freshInstance = true;
            return $this->requestEntries[$name];
        }
        $this->freshInstance = false;
        return $this->get($name, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function setRaw($name, ServiceInterface $rawDefinition)
    {
        $name = $this->normalize($name);
        if (!($rawDefinition instanceof DefinitionInterface)) {
            throw new InvalidArgumentException("Definition of service '$name' must be instance of " . DefinitionInterface::class);
        }
        return $this->definitions[$name] = $rawDefinition;
    }

    /**
     * @inheritDoc
     */
    public function getRaw($name)
    {
        $name = $this->normalize($name);
        return $this->getService($name)->getDefinition();
    }

    /**
     * @inheritDoc
     */
    public function getService($name)
    {
        $name = $this->normalize($name);
        if ($this->has($name)) {
            return $this->getDefinition($name);
        }
        throw new InvalidArgumentException("Service '$name' wasn't found in the dependency injection container");
    }

    /**
     * @inheritDoc
     */
    public function has($name)
    {
        $name = $this->normalize($name);
        return isset($this->definitions[$name])
            || array_key_exists($name, $this->singletonEntries)
            || array_key_exists($name, $this->requestEntries)
            || $this->definitionResolver->isResolvable($name);
    }

    /**
     * @inheritDoc
     */
    public function wasFreshInstance()
    {
        return $this->freshInstance;
    }

    /**
     * @inheritDoc
     */
    public function getServices()
    {
        return $this->definitions;
    }

    /**
     * @inheritDoc
     */
    public function startRequest()
    {
        $this->requestEntries = [];
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function endRequest()
    {
        $this->requestEntries = [];
        return $this;
    }
}
