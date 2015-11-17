<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\TextArea as TextAreaElement;

class TextArea extends Input
{
    protected static $elementClass = TextAreaElement::class;
}
