<?php
namespace PhalconX\Annotation;

/**
 * Annotation context
 */
class Context
{
    const TYPE_CLASS = 'class';
    const TYPE_METHOD = 'method';
    const TYPE_PROPERTY = 'property';

    /**
     * annotation class
     */
    private $class;

    /**
     * annotation declaring class
     */
    private $declaringClass;

    /**
     * annotation type: class, method, property
     */
    private $type;

    /**
     * annotation method name or property name
     */
    private $name;

    /**
     * annotation source file
     */
    private $file;

    /**
     * annotation source line
     */
    private $line;
    
    /**
     * Constructor.
     */
    public function __construct($data)
    {
        $this->class = $data['class'];
        $this->declaringClass = $data['declaringClass'];
        $this->type = $data['type'];
        $this->name = $data['name'];
        $this->file = $data['file'];
        $this->line = $data['line'];
    }

    public function isOnClass()
    {
        return $this->type == self::TYPE_CLASS;
    }

    public function isOnProperty()
    {
        return $this->type == self::TYPE_PROPERTY;
    }

    public function isOnMethod()
    {
        return $this->type == self::TYPE_METHOD;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function __toString()
    {
        return "{$this->type} {$this->class}"
            . ($this->type == self::TYPE_METOHD ? "::" . $this->name
               : ($this->type == self::TYPE_PROPERTY ? "->" . $this->name : ""))
            . " at {$this->file}:{$this->line}";
    }
    
    /**
     * Gets dummy context
     */
    public static function dummy()
    {
        static $dummyContext;
        if (!$dummyContext) {
            $dummyContext = new self([
                'class' => '',
                'declaringClass' => '',
                'type' => '',
                'name' => '',
                'file' => '',
                'line' => ''
            ]);
        }
        return $dummyContext;
    }
}
