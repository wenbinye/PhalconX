<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Hidden as HiddenElement;

class Hidden extends Input
{
    protected static $elementClass = HiddenElement::class;
}
