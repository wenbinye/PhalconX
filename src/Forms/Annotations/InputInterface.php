<?php
namespace PhalconX\Forms\Annotations;

use PhalconX\Validation\Form;

interface InputInterface
{
    /**
     * Creates form element object
     *
     * @return Phalcon\Forms\Element
     */
    public function getElement(Form $form);
}
