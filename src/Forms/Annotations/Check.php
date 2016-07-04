<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Check as CheckElement;

class Check extends Input
{
    protected static $elementClass = CheckElement::class;
}
