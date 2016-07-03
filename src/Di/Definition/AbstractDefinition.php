<?php
namespace PhalconX\Di\Definition;

use PhalconX\Di\DefinitionInterface;
use PhalconX\Di\Definition\Helper\DefinitionHelperInterface;
use PhalconX\Di\Scope;
use Phalcon\Di\Exception;

abstract class AbstractDefinition implements DefinitionInterface
{
    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var mixed $definition
     */
    protected $definition;

    /**
     * @var string $scope
     */
    protected $scope;

    public function __construct($name, $definition, $shared = null)
    {
        $this->name = $name;
        $this->setDefinition($definition);
        if ($shared !== null) {
            $this->setShared($shared);
        }
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function setShared($shared)
    {
        $this->scope = ($shared ? Scope::SINGLETON : Scope::PROTOTYPE);
    }

    /**
     * @inheritDoc
     */
    public function isShared()
    {
        return $this->scope = Scope::SINGLETON;
    }

    /**
     * @inheritDoc
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @inheritDoc
     */
    public function setParameter($position, array $parameter)
    {
        throw new Exception("Cannot change parameter");
    }

    /**
     * @inheritDoc
     */
    public static function __set_state(array $attributes)
    {
        if (!isset($attributes['name'])) {
            throw new Exception("The attribute 'name' is required");
        }
        if (!isset($attributes['definition'])) {
            throw new Exception("The attribute 'definition' is required");
        }
        if (!isset($attributes['scope'])) {
            throw new Exception("The attribute 'scope' is required");
        }
        return new static($attributes['name'], $attributes['definition'], $attributes['scope']);
    }

    /**
     * @inheritDoc
     */
    public function getScope()
    {
        if ($this->scope === null) {
            return Scope::SINGLETON;
        }
        return $this->scope;
    }

    /**
     * @inheritDoc
     */
    public function setScope($scope = null)
    {
        $this->scope = $scope;
        return $this;
    }

    protected function createDefinition($name, $value)
    {
        if ($value instanceof DefinitionHelperInterface) {
            return $value->getDefinition($name);
        } elseif ($value instanceof DefinitionInterface) {
            return $value;
        } elseif (is_array($value)) {
            return new ArrayDefinition($name, $value);
        } else {
            return new ValueDefinition($name, $value);
        }
    }
}
