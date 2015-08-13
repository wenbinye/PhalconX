<?php
namespace PhalconX\Util;

use PhalconX\Util;
use PhalconX\Annotations\Validator\IsA;
use PhalconX\Annotations\ContextType;

/**
 * convert array, object, json data to an instance of certain class
 */
class ObjectMapper
{
    const JSON = 'json';
    const OBJECT = 'object';

    private $annotationName = 'IsA';

    private $annotations;
    private $reflection;
    private $modelsMetadata;
    private $logger;

    public function __construct($options = null)
    {
        if (isset($options['annotation'])) {
            $this->annotationName = $options['annotation'];
        }
        $this->annotations = Util::service('annotations', $options);
        $this->reflection = Util::service('reflection', $options);
        $this->modelsMetadata = Util::service('modelsMetadata', $options, false);
        $this->logger = Util::service('logger', $options, false);
    }
    
    public function map($data, $clz, $format = null)
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
                    $val = $this->arrayMap($val, $type->className, $format);
                } else {
                    $val = $this->map($val, $type->className, $format);
                }
                $obj->$key = $val;
            } elseif (property_exists($obj, $key)) {
                $obj->$key = $val;
            }
        }
        return $obj;
    }

    private function getPropertyTypes($clz)
    {
        if ($this->modelsMetadata) {
            $propertyTypes = $this->modelsMetadata->read($clz . '.types');
        }
        if (!isset($propertyTypes)) {
            $annotations = $this->annotations->getAnnotations($clz, IsA::CLASS, ContextType::T_PROPERTY);
            $propertyTypes = [];
            foreach ($annotations as $annotation) {
                $annotation->setAnnotations($this->annotations);
                $propertyTypes[$annotation->getProperty()] = (object) [
                    'isArray' => $annotation->isArray(),
                    'className' => $annotation->getType()
                ];
            }
            if ($this->logger) {
                $this->logger->info("Parse types from class " . $clz);
            }
            if ($this->modelsMetadata) {
                $this->modelsMetadata->write($clz.'.types', $propertyTypes);
            }
        }
        return $propertyTypes;
    }
    
    public function arrayMap($data, $clz, $format = null)
    {
        if ($format === self::JSON) {
            return $this->arrayMap(json_decode($data, true), $clz);
        } elseif (is_array($data)) {
            $result = [];
            foreach ($data as $elem) {
                $result[] = $this->map($elem, $clz, $format);
            }
            return $result;
        } else {
            throw new \InvalidArgumentException("The object data should be an array");
        }
    }
}
