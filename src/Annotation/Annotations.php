<?php
namespace PhalconX\Annotation;

use Phalcon\Di;
use Phalcon\Annotations\Reader;

class Annotations
{
    private $imports = [];
    private $parser;

    /**
     * Constructor
     * @param
     */
    public function __construct(Reader $parser = null, $logger = null)
    {
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

    public function import(array $classes)
    {
        foreach ($classes as $class => $alias) {
            if (is_integer($class)) {
                $class = $alias;
                $alias = self::getClassName($class);
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
     * @return Collection
     */
    public function get($class)
    {
        $parsed = $this->parser->parse($class);
        if (!is_array($parsed)) {
            return new Collection([]);
        }
        $context = [
            'class' => $class,
            'declaringClass' => $class,
            'type' => Context::TYPE_CLASS
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
        return new Collection($annotations);
    }

    private function create($annotation, $context)
    {
        $name = $annotation['name'];
        if (!$this->isValidName($name)) {
            return null;
        }
        $annotationClass = $this->resolveClassName($name, $context['declaringClass']);
        if (!$annotationClass) {
            if (isset($this->imports[$name])) {
                $annotationClass = $this->imports
            }
            $this->logger->warn("Unknown annotation '$name' at {$annotation['file']}:{$annotation['line']}");
            return null;
        }
        
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

    private static function getClassName($name)
    {
        $pos = strrpos($name, '\\');
        if ($pos === false) {
            return $name;
        } else {
            return substr($name, $pos+1);
        }
    }
}
