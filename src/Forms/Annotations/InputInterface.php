<?php
namespace PhalconX\Forms\Annotations;

use PhalconX\Validation\Validation;

interface InputInterface
{
    /**
     * Creates form element object
     *
     * @return Phalcon\Forms\Element
     */
    public function getElement(Validation $form);
}
