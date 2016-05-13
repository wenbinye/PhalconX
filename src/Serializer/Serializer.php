<?php
namespace PhalconX\Serializer;

use Phalcon\Cache;
use Phalcon\Text;
use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use PhalconX\Validation\Annotations\IsA;
use PhalconX\Validation\Annotations\IsArray;
use PhalconX\Serializer\Annotations\SerializeName;
use PhalconX\Helper\ClassResolver;
use PhalconX\Exception\BadAnnotationException;

class Serializer implements InjectionAwareInterface
{
    /**
     * @var \PhalconX\Annotation\Annotations;
     */
    private $annotations;
    /**
     * @var Cache\BackendInterface
     */
    private $cache;
    /**
     * @var ClassResolver
     */
    private $classResolver;
    /**
     * @var DiInterface
     */
    private $di;

    /**
     * Serializes data into json
     */
    public function serialize($obj)
    {
        return json_encode($this->normalize($obj));
    }

    /**
     * Deserializes json to object
     */
    public function deserialize($jsonString, $class)
    {
        return $this->denormalize(self::decodeJson($jsonString), $class);
    }

    /**
     * Denormalizes data back into an object of the given class.
     */
    public function denormalize(array $arr, $class)
    {
        $properties = $this->getClassProperties($class);
        $obj = new $class;
        $is_array = ($obj instanceof ArrayAccess);
        foreach ($properties as $name => $prop) {
            if (isset($prop['serializeName'])) {
                $name = $prop['serializeName'];
            }
            if (!isset($arr[$name])) {
                continue;
            }
            $value = $arr[$name];
            if (isset($prop['type'])) {
                if ($prop['type'] === 'array') {
                    $value = array_map(function ($elem) use ($prop) {
                        return $this->denormalize($elem, $prop['element']);
                    }, $value);
                } else {
                    $value = $this->denormalize($value, $prop['type']);
                }
            }
            if (isset($prop['setter'])) {
                $setter = $prop['setter'];
                $obj->$setter($value);
            } elseif (isset($prop['name'])) {
                $key = $prop['name'];
                $obj->$key = $value;
            } elseif ($is_array) {
                $obj[$key] = $value;
            }
        }
        return $obj;
    }

    /**
     * Normalizes the object into an array of scalars|arrays.
     */
    public function normalize($obj)
    {
        $properties = $this->getClassProperties(get_class($obj));
        $data = [];
        foreach ($properties as $name => $prop) {
            if (isset($prop['getter'])) {
                $getter = $prop['getter'];
                $value = $obj->$getter();
            } else {
                $key = $prop['name'];
                $value = $obj->$key;
            }
            if (isset($prop['type'])) {
                if ($prop['type'] === 'array') {
                    $value = array_map(function ($elem) use ($prop) {
                        return $this->normalize($elem);
                    }, $value);
                } else {
                    $value = $this->normalize($value);
                }
            }
            if (isset($prop['serializeName'])) {
                $name = $prop['serializeName'];
            }
            $data[$name] = $value;
        }
        return $data;
    }

    /**
     * gets class properties metadata
     */
    private function getClassProperties($class)
    {
        $properties = $this->getCache()->get('_PHX.serialze_properties.'. $class);
        if (!isset($properties)) {
            $classResolver = $this->getClassResolver();
            $properties = $this->getReflectionProperties($class);
            foreach ($this->getAnnotations()->get($class) as $annotation) {
                if ($annotation instanceof IsA) {
                    $properties[$this->getAnnotationProperty($annotation)]['type']
                        = $classResolver->resolve($annotation->class, $annotation->getDeclaringClass());
                } elseif ($annotation instanceof IsArray && is_scalar($annotation->element)) {
                    $name = $this->getAnnotationProperty($annotation);
                    $properties[$name]['type'] = 'array';
                    $properties[$name]['element']
                        = $classResolver->resolve($annotation->element, $annotation->getDeclaringClass());
                } elseif ($annotation instanceof SerializeName) {
                    $properties[$this->getAnnotationProperty($annotation)]['serializeName']
                        = $annotation->value;
                }
            }
            $this->getCache()->save('_PHX.serialze_properties.'.$class, $properties);
        }
        return $properties;
    }

    private function getReflectionProperties($class)
    {
        $properties = [];
        $refl = new \ReflectionClass($class);
        foreach ($refl->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }
            $properties[$prop->getName()] = [
                'isPublic' => true,
                'name' => $prop->getName()
            ];
        }
        foreach ($refl->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }
            $name = $method->getName();
            if (Text::startsWith($name, 'set')) {
                $prop = lcfirst(substr($name, 3));
                if ($prop) {
                    $params = $method->getParameters();
                    if (count($params) == 1) {
                        $properties[$prop]['setter'] = $name;
                        $type = $params[0]->getClass();
                        if ($type) {
                            $properties[$prop]['type'] = $type;
                        }
                    }
                }
            } elseif (preg_match('/(get|is|has)(.+)$/i', $name, $matches)
                      && !$method->getParameters()) {
                $properties[lcfirst($matches[2])]['getter'] = $name;
            }
        }
        return $properties;
    }

    private function getAnnotationProperty($annotation)
    {
        if ($annotation->isOnMethod() &&
            preg_match('/(get|is|has|set)(.+)$/i', $annotation->getMethodName(), $matches)) {
            return lcfirst($matches[2]);
        } elseif ($annotation->isOnProperty()) {
            return $annotation->getPropertyName();
        } else {
            throw new BadAnnotationException($annotation, "Annotation SerialzeName should add to property or getter/setters");
        }
    }
    
    public static function decodeJson($json)
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException("Malformed json");
        }
        return $data;
    }

    public function getAnnotations()
    {
        if (!$this->annotations) {
            $this->annotations = $this->getDi()->getAnnotations();
        }
        return $this->annotations;
    }

    public function setAnnotations($annotations)
    {
        $this->annotations = $annotations;
        return $this;
    }

    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = $this->getAnnotations()->getCache();
        }
        return $this->cache;
    }

    public function setCache($cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function getClassResolver()
    {
        if (!$this->classResolver) {
            $this->classResolver = new ClassResolver($this->getCache());
        }
        return $this->classResolver;
    }

    public function setClassResolver($classResolver)
    {
        $this->classResolver = $classResolver;
        return $this;
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
}
