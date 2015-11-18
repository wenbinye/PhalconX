<?php
namespace PhalconX\Php;

use PhalconX\Exception\ClassNotExistException;

class ClassHierarchy
{
    /**
     * @var array all class names. the index is class id
     */
    private $classes;
    /**
     * @var array all interface names. the index is interface id
     */
    private $interfaces;
    /**
     * @var array key is class id, value is parent class id
     */
    private $extends;
    /**
     * @var array key is class id, value is interface id of implemented interfaces
     */
    private $implements;
    /**
     * @var array key is interface id, value is interface id of extended interfaces
     */
    private $interfaceExtends;

    public function __construct()
    {
        $this->classes = new ReverseMap;
        $this->interfaces = new ReverseMap;
        $this->extends = new ReverseArray;
        $this->implements = new ReverseArray;
        $this->interfaceExtends = new ReverseArray;
    }
    
    public function addDeclared()
    {
        foreach (get_declared_classes() as $class) {
            $refl = new \ReflectionClass($class);
            $parent = $refl->getParentClass();
            $this->addClass(
                $class,
                $parent ? $parent->getName() : null,
                $refl->getInterfaceNames()
            );
        }
        foreach (get_declared_interfaces() as $interface) {
            $refl = new \ReflectionClass($interface);
            $this->addInterface($interface, $refl->getInterfaceNames());
        }
        return $this;
    }

    public function getClasses()
    {
        return $this->classes->toArray();
    }

    public function getInterfaces()
    {
        return $this->interfaces->toArray();
    }
    
    /**
     * add class to hierarchy tree
     *
     * @param string $class
     * @param string $extends parent class
     * @param array $implements interfaces that implements
     * @return self
     */
    public function addClass($class, $extends = null, $implements = [])
    {
        $classId = $this->registerClass($class);
        if ($extends) {
            $parentClassId = $this->registerClass($extends);
            $this->extends[$classId] = [$parentClassId];
        }
        if (!empty($implements)) {
            $interfaceIds = [];
            foreach ($implements as $interface) {
                $interfaceIds[] = $this->registerInterface($interface);
            }
            $this->implements[$classId] = $interfaceIds;
        }
    }

    /**
     * add interface to hierarchy tree
     *
     * @param string $interface
     * @param array $extends
     * @return self
     */
    public function addInterface($interface, $extends = [])
    {
        $interfaceId = $this->registerInterface($interface);
        if (!empty($extends)) {
            $interfaceIds = [];
            foreach ($extends as $name) {
                $interfaceIds[] = $this->registerInterface($name);
            }
            $this->interfaceExtends[$interfaceId] = $interfaceIds;
        }
    }

    /**
     * gets parent class
     *
     * @param string $class
     * @retutn string|null|false
     */
    public function getParent($class)
    {
        $classId = $this->getClassId($class);
        if (!isset($classId)) {
            return false;
        }
        $parentClassId = $this->extends[$classId];
        return isset($parentClassId) ? $this->classes[$parentClassId[0]] : null;
    }

    /**
     * gets all parent classes
     *
     * @param string $class
     * @return array
     */
    public function getAncestors($class)
    {
        $ancestors = [];
        while (true) {
            $parent = $this->getParent($class);
            if ($parent) {
                $ancestors[] = $parent;
                $class = $parent;
            } else {
                break;
            }
        }
        return $ancestors;
    }

    /**
     * gets implemented interfaces
     *
     * @param string $class
     * @return array|false
     */
    public function getImplements($class)
    {
        $interfaces = [];
        $classId = $this->getClassId($class);
        if (isset($classId)) {
            if (isset($this->implements[$classId])) {
                foreach ($this->implements[$classId] as $interfaceId) {
                    $interfaces[$this->interfaces[$interfaceId]] = 1;
                }
            }
            foreach ($this->getAncestors($class) as $parent) {
                foreach ($this->getImplements($parent) as $interface) {
                    $interfaces[$interface] = 1;
                }
            }
        }
        return array_keys($interfaces);
    }

    /**
     * checks whether class exists
     *
     * @param string $class
     * @return boolean
     */
    public function classExists($class)
    {
        $classId = $this->getClassId($class);
        return isset($classId);
    }

    /**
     * checks whether interface exists
     *
     * @param string $interface
     * @return boolean
     */
    public function interfaceExists($interface)
    {
        $interfaceId = $this->getInterfaceId($interface);
        return isset($interfaceId);
    }

    /**
     * checks the class is subclass or implements the test class
     *
     * @param string $class
     * @param string $test_class class or interface name
     * @return boolean
     */
    public function isA($class, $test_class)
    {
        $test_class = $this->normalize($test_class);
        if ($this->classExists($test_class)) {
            return in_array($test_class, $this->getAncestors($class));
        } elseif ($this->interfaceExists($test_class)) {
            return in_array($test_class, $this->getImplements($class));
        } else {
            throw new ClassNotExistException($test_class);
        }
    }

    /**
     * gets all subclass of the class or interface
     *
     * @param string $class
     * @return array
     */
    public function getSubClasses($class)
    {
        $class = $this->normalize($class);
        if ($this->classExists($class)) {
            return $this->getSubclassOfClass($class);
        } elseif ($this->interfaceExists($class)) {
            return $this->getSubclassOfInterface($class);
        } else {
            throw new ClassNotExistException($class);
        }
    }

    private function getSubclassOfClass($class)
    {
        $subclasses = [];
        $classId = $this->classes->getKey($class);
        $subclassIds = $this->extends->getKeys($classId);
        if (isset($subclassIds)) {
            foreach ($subclassIds as $subclassId) {
                $subclasses[] = $this->classes[$subclassId];
            }
            $subsub = [];
            foreach ($subclasses as $subclass) {
                $subsub = array_merge($subsub, $this->getSubClasses($subclass));
            }
            $subclasses = array_merge($subclasses, $subsub);
        }
        return $subclasses;
    }

    private function getSubInterfaces($interface)
    {
        $subinterfaces = [];
        $interfaceId = $this->interfaces->getKey($interface);
        if (isset($interfaceId)) {
            $subinterfaceIds = $this->interfaceExtends->getKeys($interfaceId);
            if (isset($subinterfaceIds)) {
                foreach ($subinterfaceIds as $subinterfaceId) {
                    $subinterfaces[] = [$this->interfaces[$subinterfaceId]];
                }
                $subsub = [];
                foreach ($subinterfaces as $subinterface) {
                    $subsub = array_merge($subsub, $this->getSubInterface($subinterface));
                }
                $subinterfaces = array_merge($subinterfaces, $subsub);
            }
        }
        return $subinterfaces;
    }

    private function getSubclassOfInterface($interface)
    {
        $subclasses = [];
        $interfaceId = $this->interfaces->getKey($interface);
        $classIds = $this->implements->getKeys($interfaceId);
        if (isset($classIds)) {
            foreach ($classIds as $classId) {
                $subclasses[] = $this->classes[$classId];
            }
            $subsub = [];
            foreach ($subclasses as $subclass) {
                $subsub = array_merge($subsub, $this->getSubclassOfClass($subclass));
            }
            $subclasses = array_merge($subclasses, $subsub);
        }
        foreach ($this->getSubInterfaces($interfaceId) as $subinterface) {
            $subclasses = array_merge($subclasses, $this->getSubclassOfInterface($subinterface));
        }
        return $subclasses;
    }

    private function getClassId($class)
    {
        return $this->classes->getKey($this->normalize($class));
    }

    private function getInterfaceId($interface)
    {
        return $this->interfaces->getKey($this->normalize($interface));
    }

    private function registerInterface($interface)
    {
        return $this->interfaces->push($this->normalize($interface));
    }

    private function registerClass($class)
    {
        return $this->classes->push($this->normalize($class));
    }

    private function normalize($name)
    {
        return ltrim($name, '\\');
    }
}
