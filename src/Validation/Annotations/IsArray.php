<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Validation\Validators\IsArray as IsArrayValidator;
use PhalconX\Annotation\Annotation;
use PhalconX\Validation\Validation;
use PhalconX\Validation\ValidatorFactory;

class IsArray extends Annotation implements ValidatorInterface
{
    protected static $DEFAULT_PROPERTY = 'element';

    public $element;
    
    public $message;

    public function getValidator(Validation $validation)
    {
        $args = ['message' => $this->message];
        if (isset($this->element)) {
            $factory = new ValidatorFactory($validation);
            $options = $this->element;
            if (!is_array($options)) {
                $options = ['type' => $options];
            }
            $args['validators'] = $factory->create($options, $this->getContext());
        }
        return new IsArrayValidator($args);
    }
}
