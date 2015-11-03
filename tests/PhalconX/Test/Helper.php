<?php
namespace PhalconX\Test;

use PhalconX\Annotation\Context;

class Helper
{
    public static function createAnnotationContext($obj, $type, $name)
    {
        return new Context([
            'class' => get_class($obj),
            'declaringClass' => get_class($obj),
            'type' => $type,
            'name' => $name,
            'file' => __FILE__,
            'line' => __LINE__
        ]);
    }
}
