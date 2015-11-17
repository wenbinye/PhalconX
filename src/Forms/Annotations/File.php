<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\File as FileElement;

class File extends Input
{
    protected static $elementClass = FileElement::class;
}
