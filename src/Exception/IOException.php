<?php
namespace PhalconX\Exception;

class IOException extends Exception
{
    private $path;
    
    public function __construct($message, $code = 0, \Exception $previous = null, $path = null)
    {
        $this->path = $path;
        parent::__construct($message, $code, $previous);
    }

    public function getPath()
    {
        return $this->path;
    }
}
