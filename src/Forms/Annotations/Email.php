<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Email as EmailElement;

class Email extends Input
{
    protected static $elementClass = EmailElement::class;
}
