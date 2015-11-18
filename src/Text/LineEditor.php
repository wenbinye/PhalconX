<?php
namespace PhalconX\Text;

class LineEditor
{
    private $inserts;

    private $lines;
    
    public function __construct($text)
    {
        $this->lines = explode("\n", $text);
    }

    public function __toString()
    {
        $lines = [];
        $insertLine = null;
        if ($this->inserts) {
            ksort($this->inserts);
            $it = new \ArrayIterator($this->inserts);
            $insertLine = $it->key();
        }
        foreach ($this->lines as $offset => $line) {
            if (isset($insertLine) && $offset == $insertLine) {
                $lines = array_merge($lines, $it->current());
                $it->next();
                $insertLine = $it->valid() ? $it->key() : null;
            }
            if (isset($line)) {
                $lines[] = $line;
            }
        }
        return implode("\n", $lines);
    }

    public function insert($startLine, $text)
    {
        $this->inserts[$startLine-1][] = $text;
    }

    public function delete($startLine, $lines)
    {
        foreach (range(0, $lines-1) as $offset) {
            $this->lines[$startLine+$offset-1] = null;
        }
    }

    public function replace($startLine, $lines, $text)
    {
        if ($lines > 0) {
            $this->delete($startLine, $lines);
        }
        return $this->insert($startLine, $text);
    }

    public function get($line)
    {
        return isset($this->lines[$line-1]) ? $this->lines[$line-1] : null;
    }
}
