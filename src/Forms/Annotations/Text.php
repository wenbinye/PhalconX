<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Text as TextElement;

class Text extends Input
{
    protected static $elementClass = TextElement::class;
}
