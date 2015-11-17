<?php
namespace PhalconX\Php\Lint\Reporters;

use PhalconX\Php\Lint\Errors\ErrorInterface;

class TextReporter implements ReporterInterface
{
    private $errors = [];
    
    public function add(ErrorInterface $error)
    {
        $this->errors[] = 'Fatal error: ' . $error->getDescription()
                        . $this->getLineInfo($error->getFile(), $error->getLine());
    }

    public function __toString()
    {
        return implode("\n", $this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function getLineInfo($file, $line)
    {
        $info = $file ? ' in ' . $file . ' ' : ' ';
        if ($line == -1) {
            return $info . 'on unknown line';
        } else {
            return $info . 'on line ' . $line;
        }
    }
}
