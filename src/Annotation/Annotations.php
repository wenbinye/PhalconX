<?php
namespace PhalconX\Annotation;

use Phalcon\Annotations\Reader;
use Phalcon\Annotations\Annotation as PhalconAnnotation;
use Phalcon\Cache;
use Phalcon\Logger;
use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use PhalconX\Helper\ClassHelper;
use PhalconX\Helper\ClassResolver;

class Annotations implements InjectionAwareInterface
{
    /**
     * Automatic imported classes
     * Imported classes can directly use without import
     */
    private $imports = [];

    /**
     * @var Cache\BackendInterface cache object to store annotations
     */
    private $cache;

    /**
     * @var Reader annotation reader
     */
    private $parser;

    /**
     * @var Logger\AdapterInterface
     */
    private $logger;

    /**
     * @var ClassResolver
     */
    private $classResolver;

    /**
     * @var DiInterface
     */
    private $di;

    /**
     * @var array
     */
    private $extensions = ['php'];

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
        $annotations = $this->getCache()->get('_PHX.annotations_scan.'.$dir);
        if (!isset($annotations)) {
            $annotations = [];
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            $regex = '#\.(' . implode("|", array_map('preg_quote', $this->extensions)) . ')$#';
            foreach (new \RegexIterator($it, $regex) as $file => $fileInfo) {
                foreach (ClassHelper::getClasses($file) as $class) {
                    $annotations = array_merge($annotations, $this->get($class));
                }
            }
            $this->getCache()->save('_PHX.annotations_scan.'.$dir, $annotations);
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
        $annotations = $this->getCache()->get('_PHX.annotations.' . $class);
        if (!isset($annotations)) {
            $annotations = $this->getAnnotations($class);
            $this->getCache()->save('_PHX.annotations.' . $class, $annotations);
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
        $parsed = $this->getParser()->parse($class);
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
        $logger = $this->getLogger();
        $name = $annotation['name'];
        if (!$this->isValidName($name)) {
            return null;
        }
        $annotationClass = $this->resolveClassName($name, $context['declaringClass']);
        if (!$annotationClass) {
            if (isset($this->imports[$name])) {
                $annotationClass = $this->imports[$name];
            } else {
                $logger->warning("Unknown annotation '$name' at {$annotation['file']}:{$annotation['line']}");
                return null;
            }
        }
        if (!class_exists($annotationClass)) {
            $logger->warning("Annotation class '$annotationClass' does not exist"
                             ." at {$annotation['file']}:{$annotation['line']}");
            return null;
        }
        if (!is_subclass_of($annotationClass, Annotation::class)) {
            $logger->warning("Annotation class '$annotationClass' at {$annotation['file']}:{$annotation['line']}"
                             ." is not subclass of " . Annotation::class);
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
        return $this->getClassResolver()->resolve($name, $declaringClass);
    }

    public function getImports()
    {
        return $this->imports;
    }

    public function getDi()
    {
        if ($this->di === null) {
            $this->di = Di::getDefault();
        }
        return $this->di;
    }

    public function setDi(DiInterface $di)
    {
        $this->di = $di;
        return $this;
    }
    
    public function getCache()
    {
        if ($this->cache === null) {
            $di = $this->getDi();
            $this->cache = $di->has('apcCache') ? $di->getApcCache()
                         : new Cache\Backend\Memory(new Cache\Frontend\None);
        }
        return $this->cache;
    }

    public function setCache($cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function getParser()
    {
        if ($this->parser === null) {
            $this->parser = new Reader;
        }
        return $this->parser;
    }

    public function setParser(Reader $reader)
    {
        $this->parser = $reader;
        return $this;
    }

    /**
     * @return Logger\AdapterInterface
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $di = $this->getDi();
            if ($di->has('logger')) {
                $this->logger = $di->getLogger();
            } else {
                $logger = new Logger\Adapter\Stream('php://stderr');
                $logger->setLogLevel(Logger::WARNING);
                $this->logger = $logger;
            }
        }
        return $this->logger;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function getClassResolver()
    {
        if ($this->classResolver === null) {
            $this->classResolver = new ClassResolver($this->getCache());
        }
        return $this->classResolver;
    }

    public function setClassResolver(ClassResolver $resolver)
    {
        $this->classResolver = $resolver;
        return $this;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function setExtensions($extensions)
    {
        $this->extensions = $extensions;
        return $this;
    }
}
