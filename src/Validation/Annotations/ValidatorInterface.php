<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Validation\Form;

interface ValidatorInterface
{
    /**
     * Creates validator object
     *
     * @return Phalcon\Validation\Validator
     */
    public function getValidator(Form $form);
}
