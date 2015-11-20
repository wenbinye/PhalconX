<?php
namespace PhalconX\Helper;

use Phalcon\Cache;

/**
 * resolve full class name
 */
class ClassResolver
{
    /**
     * @var Cache\BackendInterface $cache
     */
    private $cache;
    
    public function __construct($cache = null)
    {
        $this->cache = $cache ?: new Cache\Backend\Memory(new Cache\Frontend\None);
    }

    /**
     * Gets full class name
     *
     * @param string $name
     * @param string $declaringClass
     * @return string|null
     */
    public function resolve($name, $declaringClass)
    {
        if (strpos($name, '\\') !== false) {
            return $name;
        }
        $imports = $this->cache->get('_PHX.imports.'.$declaringClass);
        if (!isset($imports)) {
            $imports = ClassHelper::getImports($declaringClass);
            $this->cache->save('_PHX.imports.'.$declaringClass, $imports);
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
