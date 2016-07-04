<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Numeric as NumericElement;

class Numeric extends Input
{
    protected static $elementClass = NumericElement::class;
}
