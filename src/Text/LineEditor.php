<?php
namespace PhalconX\Text;

/**
 * utility class to help edit using line number
 */
class LineEditor
{
    /**
     * @var array insert lines
     */
    private $inserts;

    /**
     * @var array buffers
     */
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

    /**
     * inserts content at $line
     *
     * @param int $line
     * @param string $text content to insert
     */
    public function insert($line, $text)
    {
        $this->inserts[$line-1][] = $text;
        return $this;
    }

    /**
     * deletes the content at $line
     *
     * @param int $line
     * @param int $lines number of lines to delete
     */
    public function delete($line, $lines)
    {
        foreach (range(0, $lines-1) as $offset) {
            $this->lines[$line+$offset-1] = null;
        }
        return $this;
    }

    /**
     * replaces the content at $line
     *
     * @param int $line
     * @param int $lines number of lines to replace
     * @param string text
     */
    public function replace($line, $lines, $text)
    {
        if ($lines > 0) {
            $this->delete($line, $lines);
        }
        return $this->insert($line, $text);
    }

    /**
     * Gets content of the line
     *
     * @return string return null if content deleted
     */
    public function get($line)
    {
        return isset($this->lines[$line-1]) ? $this->lines[$line-1] : null;
    }
}
