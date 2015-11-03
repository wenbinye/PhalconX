<?php
namespace PhalconX\Forms\Annotations;

interface InputInterface
{
    /**
     * Creates form element object
     *
     * @return Phalcon\Forms\Element
     */
    public function getElement();
}
