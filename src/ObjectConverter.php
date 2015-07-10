<?php
namespace PhalconX;

/**
 * convert array, object, json data to an instance of certain class
 */
class ObjectConverter
{
    const JSON = 'json';
    const OBJECT = 'object';

    private $annotationName = 'IsA';

    private $annotations;
    private $reflection;
    private $cache;
    private $logger;

    public function __construct($options = null)
    {
        if (isset($options['annotation'])) {
            $this->annotationName = $options['annotation'];
        }
        $this->annotations = Util::service('annotations', $options);
        $this->reflection = Util::service('reflection', $options);
        $this->cache = Util::service('cache', $options, false);
        $this->logger = Util::service('logger', $options, false);
    }
    
    public function convert($data, $clz, $format = null)
    {
        if ($format === self::JSON) {
            return $this->convertObject(json_decode($data, true), $clz, null);
        } elseif ($format === self::OBJECT) {
            return $this->convertObject((array) $data, $clz, $format);
        } else {
            return $this->convertObject($data, $clz, null);
        }
    }

    private function convertObject(array $data, $clz, $format)
    {
        $propertyTypes = $this->getPropertyTypes($clz);
        $obj = new $clz;
        foreach ($data as $key => $val) {
            if (isset($propertyTypes[$key])) {
                $type = $propertyTypes[$key];
                if ($type->isArray) {
                    $val = $this->convertArray($val, $type->className, $format);
                } else {
                    $val = $this->convert($val, $type->className, $format);
                }
            }
            $obj->$key = $val;
        }
        return $obj;
    }

    private function getPropertyTypes($clz)
    {
        if ($this->cache) {
            $propertyTypes = $this->cache->get($clz . '.types');
        }
        if (!isset($propertyTypes)) {
            $properties = $this->annotations->getProperties($clz);
            $propertyTypes = [];
            foreach ($properties as $name => $annotations) {
                foreach ($annotations as $annotation) {
                    if ($annotation->getName() === $this->annotationName) {
                        $args = $annotation->getArguments();
                        $alias = isset($args['class']) ? $args['class'] : $args[0];
                        $propertyTypes[$name] = $this->resolveType($alias, $clz);
                    }
                }
            }
            if ($this->logger) {
                $this->logger->info("Parse types from class " . $clz);
            }
            if ($this->cache) {
                $this->cache->save($clz.'.types', $propertyTypes);
            }
        }
        return $propertyTypes;
    }
    
    private function resolveType($name, $clz)
    {
        $isArray = false;
        if (strpos($name, '[]') !== false) {
            $isArray = true;
            $name = substr($name, 0, -2);
        }
        $type = $this->reflection->resolveImport($name, $clz);
        return (object) array('className' =>$type, 'isArray' => $isArray);
    }
    
    public function convertArray($data, $clz, $format = null)
    {
        if ($format === self::JSON) {
            return $this->convertArray(json_decode($data, true), $clz);
        } elseif (is_array($data)) {
            $result = [];
            foreach ($data as $elem) {
                $result[] = $this->convert($elem, $clz, $format);
            }
            return $result;
        } else {
            throw new \InvalidArgumentException("The object data should be an array");
        }
    }
}
