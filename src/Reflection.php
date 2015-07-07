<?php
namespace PhalconX;

class Reflection
{
    private $cache;
    private $logger;
    
    public function __construct($options = null)
    {
        $this->cache = Util::service('cache', $options, false);
        $this->logger = Util::service('logger', $options, false);
    }
    
    public function resolveImport($name, $clz)
    {
        if ($name[0] == '\\') {
            return $name;
        }
        if ($this->cache) {
            $imports = $this->cache->get($clz.'.imports');
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
                } elseif ($token[0] === T_CLASS ) {
                    break;
                }
            }
            if ($this->logger) {
                $this->logger->info("Parse imports from class " . $clz);
            }
            if ($this->cache) {
                $this->cache->save($clz.'.imports', $imports);
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
            } else if ($explicitAlias && $isNameToken) {
                $alias .= $token[1];
            } else if ($token[0] === T_AS) {
                $explicitAlias = true;
                $alias = '';
            } else if ($token === ',') {
                $statements[$alias] = $class;
                $class = '';
                $alias = '';
                $explicitAlias = false;
            } else if ($token === ';') {
                $statements[$alias] = $class;
                break;
            } else {
                break;
            }
        }
        return $statements;
    }
}
