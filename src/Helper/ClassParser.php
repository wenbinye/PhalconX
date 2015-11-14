<?php
namespace PhalconX\Helper;

use PhalconX\Exception\IOException;
use PhalconX\Exception\Exception;

class ClassParser
{
    private $file;
    private $linum;
    
    public function __construct($file)
    {
        if (!is_readable($file)) {
            throw new IOException("Unable to read {$file}", 0, null, $file);
        }
        $this->file = $file;
    }
    
    public function getClasses()
    {
        $classes = [];
        $code = file_get_contents($this->file);
        $tokens = token_get_all($code);
        $it = new \ArrayIterator($tokens);
        $namespace = null;
        try {
            while ($it->valid()) {
                $token = $it->current();
                if (is_array($token)) {
                    $this->linum = $token[2];
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
            throw new Exception("syntax error at {$this->file}:{$this->linum}");
        }
        return $classes;
    }

    /**
     * Gets all imported classes
     *
     * @return array
     */
    public function getImports()
    {
        $imports = [];
        $tokens = token_get_all(file_get_contents($this->file));
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
        return $imports;
    }

    private function parseUseStatement(&$tokens)
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
            throw new Exception("Unexpect token");
        }
        return $class;
    }
}
