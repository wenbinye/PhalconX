<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Submit as SubmitElement;

class Submit extends Input
{
    protected static $elementClass = SubmitElement::class;
}
