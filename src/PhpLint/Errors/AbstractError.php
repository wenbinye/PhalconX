<?php
namespace PhalconX\PhpLint\Errors;

abstract class AbstractError implements ErrorInterface
{
    private $file;

    private $line;

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function setLine($line)
    {
        $this->line = $line;
        return $this;
    }
}
