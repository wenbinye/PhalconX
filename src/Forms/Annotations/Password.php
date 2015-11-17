<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Password as PasswordElement;

class Password extends Input
{
    protected static $elementClass = PasswordElement::class;
}
