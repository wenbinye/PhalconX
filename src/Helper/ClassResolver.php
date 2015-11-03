<?php
namespace PhalconX\Helper;

use PhalconX\Exception\IOException;
use Phalcon\Cache\BackendInterface as Cache;

/**
 * resolve full class name
 */
class ClassResolver
{
    /**
     * @var Cache $cache
     */
    private $cache;
    
    public function __construct(Cache $cache = null)
    {
        $this->cache = $cache;
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
