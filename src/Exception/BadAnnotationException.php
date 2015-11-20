<?php
namespace PhalconX\Exception;

use Phalcon\Annotation\Annotation;

class BadAnnotationException extends Exception
{
    private $annotation;
    
    public function __construct(Annotation $annotation, $message)
    {
        parent::__construct($message . ' ' . $annotation);
    }

    public function getAnnotation()
    {
        return $this->annotation;
    }
}
