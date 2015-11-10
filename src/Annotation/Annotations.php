<?php
namespace PhalconX\Annotation;

use Phalcon\Annotations\Reader;
use Phalcon\Annotations\Annotation as PhalconAnnotation;
use Phalcon\Cache;
use PhalconX\Helper\ClassHelper;
use PhalconX\Helper\ClassResolver;
use PhalconX\Helper\FileHelper;

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
     * @var Phalcon\Logger\Adapter
     */
    private $logger;

    /**
     * @var ClassResolver
     */
    private $classResolver;

    /**
     * @var array
     */
    private $extensions = ['php'];
    
    /**
     * Constructor
     * @param Cache $cache
     * @param Logger $logger
     * @param Reader $reader
     */
    public function __construct(
        Cache\BackendInterface $cache = null,
        $logger = null,
        Reader $parser = null
    ) {
        $this->cache = $cache ?: new Cache\Backend\Memory(new Cache\Frontend\None);
        $this->logger = $logger;
        $this->parser = $parser ?: new Reader;
        $this->classResolver = new ClassResolver($cache);
    }

    /**
     * Imports annotation classes
     */
    public function import(array $classes)
    {
        foreach ($classes as $class => $alias) {
            if (is_integer($class)) {
                $class = $alias;
                $alias = ClassHelper::getSimpleName($class);
            }
            if (isset($this->imports[$alias])) {
                throw new \RuntimeException("Alias $alias for $class exists, previous is "
                                            . $this->imports[$alias]);
            }
            $this->imports[$alias] = $class;
        }
        return $this;
    }

    public function scan($dir)
    {
        $annotations = $this->cache->get('_PHX.annotations_scan.'.$dir);
        if (!isset($annotations)) {
            $annotations = [];
            foreach (FileHelper::find($dir, ['extension' => $this->extensions]) as $file => $fileInfo) {
                foreach (ClassHelper::getClasses($file) as $class) {
                    $annotations = array_merge($annotations, $this->get($class));
                }
            }
            $this->cache->save('_PHX.annotations_scan.'.$dir, $annotations);
        }
        return $this->filter($annotations);
    }
    
    /**
     * Gets all annotations in the class
     *
     * @param string $class class name
     * @return array
     */
    public function get($class)
    {
        $annotations = $this->cache->get('_PHX.annotations.' . $class);
        if (!isset($annotations)) {
            $annotations = $this->getAnnotations($class);
            $this->cache->save('_PHX.annotations.' . $class, $annotations);
        }
        return $annotations;
    }

    /**
     * Gets all annotations as iterator
     *
     * @param string $class class name
     * @return FilterIterator
     */
    public function iterate($class)
    {
        return $this->filter($this->get($class));
    }

    /**
     * Creates filter iterator on annotations
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
        $args = (new PhalconAnnotation($annotation))->getArguments() ?: [];
        return new $annotationClass($args, new Context($context));
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
     *
     * @return string
     */
    private function resolveClassName($name, $declaringClass)
    {
        return $this->classResolver->resolve($name, $declaringClass);
    }

    public function getImports()
    {
        return $this->imports;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getClassResolver()
    {
        return $this->classResolver;
    }
}
