<?php
namespace PhalconX\Helper;

/**
 * Parses use statements
 */
class ClassImportParser
{
    public function __construct($file)
    {
        if (!is_readable($file)) {
            throw new IOException("Unable to read {$file}", 0, null, $file);
        }
        $this->file = $file;
    }

    /**
     * Gets all imported classes
     *
     * @return array
     */
    public function get()
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
}
