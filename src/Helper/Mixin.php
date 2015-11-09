<?php
namespace PhalconX\Helper;

class Mixin
{
    private static $cache;
    
    public static function create($obj, $mixin)
    {
        $class = self::createMixinClass($obj, $mixin);
        return (new \ReflectionClass($clz))->newInstanceWithoutConstructor()
            ->_setOrigin($obj)
            ->_setMixin($mixin);
    }

    private static function createMixinClass($obj, $mixin)
    {
        $originClass = get_class($obj);
        $mixinClass = get_class($mixin);
        if (isset(self::$cache[$originClass][$mixinClass])) {
            return self::$cache[$originClass][$mixinClass];
        }
        
        $class = uniqid('Mixin') . '_' . str_replace('\\', '_', $originClass);
        $code = "class $class extends $originClass {\n"
            . "    use PhalconX\\Helper\\MixinTrait;\n";
        $refl = new \ReflectionClass($originClass);
        foreach ($refl->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }
            $name = $method->getName();
            if ($name[0] == '_') {
                continue;
            }
            $args = self::methodArgs($method);
            $code .= "    public function $name($args) {\n"
                . "        return \$this->_callMixinOrOrigin('$name', func_get_args());\n"
                . "    }\n";
        }
        $code .= "}";
        eval($code);
        return self::$cache[$originClass][$mixinClass] = $class;
    }
    

    private static function methodArgs($method)
    {
        $args = [];
        foreach ($method->getParameters() as $param) {
            $arg = '';
            if (($type = $param->getClass())) {
                $arg = $type->getName() . " ";
            } elseif ($param->isArray()) {
                $arg = "array ";
            }
            if ($param->isPassedByReference()) {
                $arg .= "&";
            }
            $name = $param->getName();
            if ($name == "...") {
                $arg .= '$_ = "..."';
            } else {
                $arg .= "$" . $name;
            }

            if ($param->isOptional()) {
                if ($param->isDefaultValueAvailable()) {
                    $arg .= " = " . str_replace("\n", "", var_export($param->getDefaultValue()));
                } else {
                    $arg .= " = NULL";
                }
            }
            $args[] = $arg;
        }
        return implode(',', $args);
    }
}
