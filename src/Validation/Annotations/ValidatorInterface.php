<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Validation\Validation;

interface ValidatorInterface
{
    /**
     * Creates validator object
     *
     * @return Phalcon\Validation\Validator
     */
    public function getValidator(Validation $validation);
}
