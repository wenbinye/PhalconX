<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Radio as RadioElement;

class Radio extends Input
{
    protected static $elementClass = RadioElement::class;
}
