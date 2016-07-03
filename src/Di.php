<?php
namespace PhalconX;

use Closure;
use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Phalcon\Di as PhalconDi;
use Phalcon\DiInterface as PhalconDiInterface;
use Phalcon\Di\ServiceInterface;
use Phalcon\Di\Exception;

use PhalconX\Di\DefinitionInterface;
use PhalconX\Di\Definition\Helper\DefinitionHelperInterface;
use PhalconX\Di\Definition\Resolver\DefinitionResolverInterface;
use PhalconX\Di\Definition\Resolver\ObjectDefinitionResolver;
use PhalconX\Di\Definition\FactoryDefinition;
use PhalconX\Di\Definition\ArrayDefinition;
use PhalconX\Di\Definition\ValueDefinition;
use PhalconX\Di\Scope;
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

    public function __construct()
    {
        parent::__construct();
        $this->definitionResolver = new ObjectDefinitionResolver;
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

    public function addDefintions(array $definitions)
    {
        foreach ($definitions as $name => $definition) {
            $this->set($name, $definition);
        }
    }

    public function addContainerDefinitions()
    {
        $this->addDefintions([
            DiInterface::class => new ValueDefinition(DiInterface::class, $this),
            PhalconDiInterface::class => new ValueDefinition(PhalconDiInterface::class, $this),
            ContainerInterface::class => new ValueDefinition(ContainerInterface::class, new Container($this))
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function set($name, $definition, $shared = false)
    {
        return $this->definitions[$name] = $this->createDefinition($name, $definition, Scope::PROTOTYPE);
    }

    /**
     * @inheritDoc
     */
    public function setShared($name, $definition)
    {
        return $this->definitions[$name] = $this->createDefinition($name, $definition, Scope::SINGLETON);
    }

    /**
     * @inheritDoc
     */
    public function remove($name)
    {
        unset($this->definitions[$name]);
        unset($this->singletonEntries[$name]);
        unset($this->requestEntries[$name]);
    }

    /**
     * @inheritDoc
     */
    public function attempt($name, $definition, $shared = false)
    {
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
        if ($scope === Scope::SINGLETON) {
            $this->singletonEntries[$name] = $value;
        } elseif ($scope === Scope::REQUEST) {
            $this->requestEntries[$name] = $value;
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getShared($name, $parameters = null)
    {
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
        return $this->getService($name)->getDefinition();
    }

    /**
     * @inheritDoc
     */
    public function getService($name)
    {
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
