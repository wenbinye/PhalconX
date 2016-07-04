<?php
namespace PhalconX\Di\Definition;

use Phalcon\DiInterface;
use Phalcon\Di\Exception;
use Phalcon\Di\InjectionAwareInterface;
use ReflectionClass;
use ReflectionException;
use PhalconX\Di\Definition\Helper\AliasDefinitionHelper;

class ObjectDefinition extends AbstractDefinition
{
    /**
     * @var array
     */
    private static $CLASS_METADATA = [];
    
    /**
     * @var array
     */
    private $constructorParameters;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var array
     */
    private $methods;

    /**
     * @var array
     */
    private $constructorParameterDefinitions;

    /**
     * @var array
     */
    private $propertyDefinitions;

    /**
     * @var array
     */
    private $methodDefinitions;

    /**
     * @var bool
     */
    private $resolved = false;

    public function setConstructorParameters($constructorParameters)
    {
        $this->constructorParameters = $constructorParameters;
        return $this;
    }

    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    public function setMethods($methods)
    {
        $this->methods = $methods;
        return $this;
    }

    public function resolve($parameters = null, DiInterface $container = null)
    {
        if (!$this->resolved) {
            $this->resolveConstructorParameters($container);
            $this->resolveProperties();
            $this->resolveMethods();
            $this->resolved = true;
        }
        $className = $this->getClassName();
        if (empty($this->constructorParameterDefinitions)) {
            $instance = new $className;
        } else {
            if ($parameters === null) {
                $parameters = $this->resolveValues($container, $this->constructorParameterDefinitions);
            }
            $instance = $this->createInstance($className, $parameters);
        }
        if (!empty($this->propertyDefinitions)) {
            foreach ($this->propertyDefinitions as $property => $definition) {
                $instance->$property = $definition->resolve(null, $container);
            }
        }
        if (!empty($this->methodDefinitions)) {
            foreach ($this->methodDefinitions as $method => $calls) {
                foreach ($calls as $args) {
                    call_user_func_array([$instance, $method], $this->resolveValues($container, $args));
                }
            }
        }
        return $instance;
    }

    protected function getClassName()
    {
        return ($this->definition === null ? $this->getName() : $this->definition);
    }

    private function createInstance($className, $parameters)
    {
        $argc = count($parameters);
        if ($argc === 0) {
            return new $className;
        } elseif ($argc === 1) {
            return new $className($parameters[0]);
        } elseif ($argc === 2) {
            return new $className($parameters[0], $parameters[1]);
        } elseif ($argc === 3) {
            return new $className($parameters[0], $parameters[1], $parameters[2]);
        } else {
            $refl = new ReflectionClass($className);
            return $refl->getConstructor()->invokeArgs($parameters);
        }
    }

    private function resolveValues($container, $definitions)
    {
        $values = [];
        foreach ($definitions as $i => $definition) {
            if ($definition === null) {
                throw new Exception("Cannot resolve paramenter $i");
            }
            $values[] = $definition->resolve(null, $container);
        }
        return $values;
    }

    private function getConstructorTypes($className)
    {
        if (isset(self::$CLASS_METADATA[$className])) {
            return self::$CLASS_METADATA[$className];
        }
        $metadata = [];

        $refl = new ReflectionClass($className);
        $paramTypes = [];
        if (($constructor = $refl->getConstructor()) !== null) {
            foreach ($constructor->getParameters() as $i => $parameter) {
                if (($class = $parameter->getClass()) !== null) {
                    $paramTypes[] = $class->getName();
                } elseif (!$parameter->isOptional()) {
                    $paramTypes[] = null;
                }
            }
        }
        $metadata['constructor'] = $paramTypes;
        
        if ($refl->implementsInterface(InjectionAwareInterface::class)) {
            $metadata['interfaces'][InjectionAwareInterface::class] = true;
        }
        return self::$CLASS_METADATA[$className] = $metadata;
    }

    private function resolveConstructorParameters($container)
    {
        $className = $this->getClassName();
        if ($this->constructorParameters === null) {
            try {
                $metadata = self::getConstructorTypes($className);
            } catch (ReflectionException $e) {
                throw new Exception(sprintf("Cannot find $className for '%s'", $this->getName()));
            }
            $args = [];
            foreach ($metadata['constructor'] as $type) {
                if ($type === null) {
                    $args[] = null;
                } else {
                    $args[] = $container->has($type)
                            ? new AliasDefinitionHelper($type)
                            : new ObjectDefinition($type, $type);
                }
            }
            if (!empty($metadata['interfaces'][InjectionAwareInterface::class])) {
                $this->methods['setDi'][] = [new ValueDefinition('di', $container)];
            }
            $this->constructorParameters = $args;
        }
        if (empty($this->constructorParameters)) {
            return;
        }
        $definitions = [];
        $prefix = $this->getClassName() . '.constructor';
        foreach ($this->constructorParameters as $i => $val) {
            if ($val === null) {
                $definitions[] = null;
            } else {
                $definitions[] = $this->createDefinition($prefix . "[{$i}]", $val);
            }
        }
        $this->constructorParameterDefinitions = $definitions;
    }

    private function resolveProperties()
    {
        if (empty($this->properties)) {
            return;
        }
        $prefix = $this->getClassName() . '.properties';
        $definitions = [];
        foreach ($this->properties as $property => $val) {
            $definitions[$property] = $this->createDefinition($prefix ."[{$property}]", $val);
        }
        $this->propertyDefinitions = $definitions;
    }

    private function resolveMethods()
    {
        if (empty($this->methods)) {
            return;
        }
        $prefix = $this->getClassName() . '.methods';
        $definitions = [];
        foreach ($this->methods as $method => $calls) {
            $callDefinitions = [];
            $methodPrefix = $prefix . "[{$method}]";
            foreach ($calls as $call) {
                $args = [];
                foreach ($call as $i => $val) {
                    $args[] = $this->createDefinition($methodPrefix . "[{$i}]", $val);
                }
                $callDefinitions[] = $args;
            }
            $definitions[$method] = $callDefinitions;
        }
        $this->methodDefinitions = $definitions;
    }
}
