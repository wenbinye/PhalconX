<?php
namespace PhalconX\Annotation;

use Phalcon\Di;
use Phalcon\Annotations\Reader;
use Phalcon\Annotations\Annotation as PhalconAnnotation;
use Phalcon\Cache\BackendInterface as Cache;
use PhalconX\Helper\ClassHelper;

class Annotations
{
    /**
     * Automatic imported classes
     * Imported classes can directly use without import
     */
    private $imports = [];

    /**
     * @var Cache cache object to store annotations
     */
    private $cache;

    /**
     * @var Reader annotation reader
     */
    private $parser;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     * @param Cache $cache
     * @param Reader $reader
     * @param Logger $logger
     */
    public function __construct(Cache $cache = null, Reader $parser = null, $logger = null)
    {
        $this->cache = $cache;
        $this->parser = $parser ?: new Reader;
        if ($logger) {
            $this->logger = $logger;
        } else {
            $di = Di::getDefault();
            if ($di->has('logger')) {
                $this->logger = $di->getLogger();
            }
        }
    }

    /**
     * Imports annotation classes
     */
    public function import(array $classes)
    {
        foreach ($classes as $class => $alias) {
            if (is_integer($class)) {
                $class = $alias;
                $alias = ClassHelper::getShortName($class);
            }
            if (isset($this->imports[$alias])) {
                throw new \RuntimeException("Alias $alias for $class exists, previous is "
                                            . $this->imports[$alias]);
            }
            $this->imports[$alias] = $class;
        }
        return $this;
    }
    
    /**
     * Gets all annotations in the class
     *
     * @param string $class class name
     * @return array
     */
    public function get($class)
    {
        if ($this->cache) {
            $annotations = $this->cache->get('__PHX.annotations.' . $class);
        }
        if (!isset($annotations)) {
            $annotations = $this->getAnnotations($class);
            if ($this->cache) {
                $this->cache->save('__PHX.annotations.' . $class, $annotations);
            }
        }
        return $annotations;
    }

    /**
     * Gets FilterIterator on annotations array
     *
     * @param array $annotations
     * @return FilterIterator
     */
    public function filter(array $annotations)
    {
        return new FilterIterator(new \ArrayIterator($annotations));
    }
    
    /**
     * parse all annotations from class
     */
    private function getAnnotations($class)
    {
        $parsed = $this->parser->parse($class);
        if (!is_array($parsed)) {
            return [];
        }
        $context = [
            'class' => $class,
            'declaringClass' => $class,
            'type' => Context::TYPE_CLASS,
            'name' => $class
        ];
        $annotations = [];
        if (!empty($parsed['class'])) {
            foreach ($parsed['class'] as $value) {
                $anno = $this->create($value, $context);
                if ($anno) {
                    $annotations[] = $anno;
                }
            }
        }
        $map = [
            'methods' => Context::TYPE_METHOD,
            'properties' => Context::TYPE_PROPERTY
        ];
        $reflection = new \ReflectionClass($class);
        foreach ($map as $type_name => $type) {
            if (!empty($parsed[$type_name])) {
                foreach ($parsed[$type_name] as $name => $values) {
                    $reflType = $type == 'method' ? $reflection->getMethod($name)
                               : $reflection->getProperty($name);
                    $context['type'] = $type;
                    $context['name'] = $name;
                    $context['declaringClass'] = $reflType->getDeclaringClass()->getName();
                    foreach ($values as $value) {
                        $anno = $this->create($value, $context);
                        if ($anno) {
                            $annotations[] = $anno;
                        }
                    }
                }
            }
        }
        return $annotations;
    }

    /**
     * create annotation object
     */
    private function create($annotation, $context)
    {
        $name = $annotation['name'];
        if (!$this->isValidName($name)) {
            return null;
        }
        $annotationClass = $this->resolveClassName($name, $context['declaringClass']);
        if (!$annotationClass) {
            if (isset($this->imports[$name])) {
                $annotationClass = $this->imports[$name];
            } else {
                if ($this->logger) {
                    $this->logger->warning("Unknown annotation '$name' at {$annotation['file']}:{$annotation['line']}");
                }
                return null;
            }
        }
        if (!class_exists($annotationClass)) {
            if ($this->logger) {
                $this->logger->warning("Annotation class '$annotationClass' does not exist"
                                    ." at {$annotation['file']}:{$annotation['line']}");
            }
            return null;
        }
        if (!is_subclass_of($annotationClass, Annotation::class)) {
            if ($this->logger) {
                $this->logger->warning("Annotation class '$annotationClass' at {$annotation['file']}:{$annotation['line']}"
                                    ." is not subclass of " . Annotation::class);
            }
            return null;
        }
        $context['file'] = $annotation['file'];
        $context['line'] = $annotation['line'];
        return new $annotationClass((new PhalconAnnotation($annotation))->getArguments(), new Context($context));
    }

    /**
     * checks the annation name
     * The first letter needs to be upcase
     *
     * @param $name name of annotation
     */
    private function isValidName($name)
    {
        return ctype_upper($name[0]);
    }

    /**
     * resolve annotation class name
     */
    private function resolveClassName($name, $declaringClass)
    {
        if ($this->cache) {
            $imports = $this->cache->get('__PHX.imports.'.$declaringClass);
        }
        if (!isset($imports)) {
            $imports = ClassHelper::getImports($declaringClass);
            if ($this->cache) {
                $this->cache->save('__PHX.imports.'.$declaringClass, $imports);
            }
        }
        if (isset($imports[$name])) {
            return $imports[$name];
        }
        $class = ClassHelper::getNamespaceName($declaringClass) . $name;
        if (class_exists($class)) {
            return $class;
        }
    }
}
