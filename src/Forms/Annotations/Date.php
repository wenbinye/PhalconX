<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Date as DateElement;

class Date extends Input
{
    protected static $elementClass = DateElement::class;
}
