<?php
namespace PhalconX\Util;

use PhalconX\Util;
use PhalconX\Exception;

class Reflection
{
    private $modelsMetadata;
    private $logger;
    
    public function __construct($options = null)
    {
        $this->modelsMetadata = Util::service('modelsMetadata', $options, false);
        $this->logger = Util::service('logger', $options, false);
    }
    
    public function resolveImport($name, $clz)
    {
        if ($name[0] == '\\') {
            return $name;
        }
        if ($this->modelsMetadata) {
            $imports = $this->modelsMetadata->read($clz.'.imports');
        }
        if (!isset($imports)) {
            $imports = [];
            $reflect = new \ReflectionClass($clz);
            $file = $reflect->getFileName();
            $tokens = token_get_all(file_get_contents($file));
            reset($tokens);
            $token = '';
            while ($token !== false) {
                $token = next($tokens);
                if (!is_array($token)) {
                    continue;
                }
                if ($token[0] === T_USE) {
                    $stmt = $this->parseUseStatement($tokens);
                    $imports += $stmt;
                } elseif ($token[0] === T_CLASS) {
                    break;
                }
            }
            if ($this->logger) {
                $this->logger->info("Parse imports from class " . $clz);
            }
            if ($this->modelsMetadata) {
                $this->modelsMetadata->write($clz.'.imports', $imports);
            }
        }
        $parts = explode('\\', $name);
        if (isset($imports[$parts[0]])) {
            $ns = $this->getNamespace($imports[$parts[0]]);
        } else {
            $ns = $this->getNamespace($clz);
        }
        return $ns . '\\' . $name;
    }

    public function getClasses($file)
    {
        $classes = [];
        $code = file_get_contents($file);
        $tokens = token_get_all($code);
        $it = Util::iterator($tokens);
        $namespace = null;
        try {
            while ($it->valid()) {
                $token = $it->current();
                if (is_array($token)) {
                    if ($token[0] == T_NAMESPACE) {
                        $namespace = $this->matchClassname($it);
                    } elseif ($token[0] == T_DOUBLE_COLON) {
                        $it->next();
                        if ($it->valid()) {
                            $it->next();
                        }
                    } elseif ($token[0] == T_CLASS || $token[0] == T_INTERFACE) {
                        $class = $this->matchClassname($it);
                        $classes[] = $namespace ? $namespace . '\\' . $class : $class;
                    }
                }
                $it->next();
            }
        } catch (Exception $e) {
            throw new Exception("解析文件类名错误 $file");
        }
        return $classes;
    }
    
    private function matchClassname($it)
    {
        $it->next();
        while ($it->valid()) {
            $token = $it->current();
            if ($token[0] != T_WHITESPACE) {
                break;
            }
            $it->next();
        }
        $class = '';
        while ($it->valid()) {
            $token = $it->current();
            if ($token[0] == T_STRING || $token[0] == T_NS_SEPARATOR) {
                $class .= $token[1];
            } else {
                break;
            }
            $it->next();
        }
        if (!$class) {
            throw new Exception("解析错误");
        }
        return $class;
    }

    private static function getNamespace($clz)
    {
        $pos = strrpos($clz, '\\');
        if ($pos === false) {
            return '';
        } else {
            return substr($clz, 0, $pos);
        }
    }
    
    private static function parseUseStatement(&$tokens)
    {
        $class = '';
        $alias = '';
        $statements = array();
        $explicitAlias = false;
        $token = '';
        while ($token !== false) {
            $token = next($tokens);
            if (is_array($token) && ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT)) {
                continue;
            }
            $isNameToken = $token[0] === T_STRING || $token[0] === T_NS_SEPARATOR;
            if (!$explicitAlias && $isNameToken) {
                $class .= $token[1];
                $alias = $token[1];
            } elseif ($explicitAlias && $isNameToken) {
                $alias .= $token[1];
            } elseif ($token[0] === T_AS) {
                $explicitAlias = true;
                $alias = '';
            } elseif ($token === ',') {
                $statements[$alias] = $class;
                $class = '';
                $alias = '';
                $explicitAlias = false;
            } elseif ($token === ';') {
                $statements[$alias] = $class;
                break;
            } else {
                break;
            }
        }
        return $statements;
    }
}
