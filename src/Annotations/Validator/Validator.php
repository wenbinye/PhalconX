<?php
namespace PhalconX\Annotations\Validator;

use Phalcon\Validation\Validator\PresenceOf;
use PhalconX\Annotations\Annotation;

abstract class Validator extends Annotation
{
    public $name;

    public $default;

    public $required;

    public function process()
    {
        $validators = [];
        if ($this->required) {
            $validators[] = new PresenceOf();
        }
        return array_merge($validators, $this->getValidators());
    }

    abstract protected function getValidators();
}
