<?php
namespace PhalconX;

use Phalcon\Di;
use Phalcon\Text;
use Phalcon\Mvc\ModuleDefinitionInterface;

class Util
{
    public static function service($name, $options = null, $required = true)
    {
        if (isset($options[$name])) {
            return $options[$name];
        }
        $di = Di::getDefault();
        if ($di->has($name)) {
            return $di->getShared($name);
        } elseif ($required) {
            throw new \UnexpectedValueException("Service '$name' is not defined yet");
        }
    }

    public static function fetch($array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }
    
    public static function now($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }

    public static function startsWith($str, $start, $stop = null)
    {
        if (isset($stop)) {
            return $str == $start || Text::startsWith($str, $start . $stop);
        } else {
            return Text::startsWith($str, $start);
        }
    }

    public static function uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    public static function template($template, $context)
    {
        return preg_replace_callback('/{([^{}]+)}/', function ($matches) use ($context) {
            return self::fetch($context, $matches[1], $matches[0]);
        }, $template);
    }

    public static function iterator($array)
    {
        return (new \ArrayObject($array))->getIterator();
    }

    public static function walkdir($dir, $callback, $ignoreHide = true)
    {
        $it = new \RecursiveDirectoryIterator($dir);
        foreach (new \RecursiveIteratorIterator($it) as $file => $fileInfo) {
            $name = $fileInfo->getFilename();
            if ($name == '.' || $name == '..') {
                continue;
            }
            if ($ignoreHide && $name[0] == '.') {
                continue;
            }
            call_user_func($callback, $file, $fileInfo);
        }
    }

    public static function parseShellArguments($args)
    {
        preg_match_all('#(?<!\\\\)("|\')(?:[^\\\\]|\\\\.)*?\1|\S+#s', $args, $matches);
        $argv = [];
        foreach ($matches[0] as $arg) {
            if (in_array($arg[0], ['"', '\''])) {
                $len = strlen($arg);
                if ($len < 2 || substr($arg, $len-1, 1) != $arg[0]) {
                    throw new Exception("Parse argument error at '$arg', unmatch quotes");
                }
                $arg = stripcslashes(substr($arg, 1, -1));
            }
            $argv[] = $arg;
        }
        return $argv;
    }

    public static function splitClassName($class)
    {
        $pos = strrpos($class, '\\');
        if ($pos !== false) {
            return [substr($class, 0, $pos), substr($class, $pos+1)];
        } else {
            return [null, $class];
        }
    }

    public static function catfile($dir, $file)
    {
        $file = ltrim($file, '/');
        if ($dir) {
            return rtrim($dir, '/') . '/' . $file;
        } else {
            return $file;
        }
    }

    public static function newInstance($class, $args = null)
    {
        $refl = new \ReflectionClass($class);
        if ($refl->getConstructor()) {
            return Di::getDefault()->get($class, [$args]);
        } else {
            return Di::getDefault()->get($class);
        }
    }
    
    public static function import($bundle, $options = null)
    {
        $di = Di::getDefault();
        if (isset($di['config']->bundles[$bundle])) {
            $bundle = self::newInstance($di['config']->bundles[$bundle], $options);
            if ($bundle instanceof ModuleDefinitionInterface) {
                $bundle->registerAutoloaders($di);
                $bundle->registerServices($di);
                return $bundle;
            } else {
                throw new Exception("Service " . $di['config']->bundles[$bundle]
                                    . " is not ModuleDefinitionInterface");
            }
        } else {
            throw new Exception("Bundle '$bundle' is not defined in configuration");
        }
    }

    public static function mixin($obj, $mixin)
    {
        $orig = get_class($obj);
        $clz = str_replace('\\', '_', $orig) . uniqid('Mixin');
        $code = "class $clz extends $orig {\n"
            ."  use PhalconX\\Util\\Mixin;\n";
        $refl = new \ReflectionClass($orig);
        foreach ($refl->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }
            $name = $method->getName();
            if ($name[0] == '_') {
                continue;
            }
            $args = self::methodArgs($method);
            $code .= "public function $name($args) { return \$this->_callMixin('$name', func_get_args()); }\n";
        }
        $code .= "}";
        eval($code);
        $refl = new \ReflectionClass($clz);
        return $refl->newInstanceWithoutConstructor()
            ->_setOriginObject($obj)->_setMixin($mixin);
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
