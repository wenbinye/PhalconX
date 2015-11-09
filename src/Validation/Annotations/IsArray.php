<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Validation\Validators\IsArray as IsArrayValidator;
use PhalconX\Annotation\Annotation;
use PhalconX\Validation\Form;
use PhalconX\Validation\ValidatorFactory;

class IsArray extends Annotation implements ValidatorInterface
{
    protected static $DEFAULT_PROPERTY = 'element';

    public $element;
    
    public $message;

    public function getValidator(Form $form)
    {
        $args = ['message' => $this->message];
        if (isset($this->element)) {
            $factory = new ValidatorFactory($form);
            $options = $this->element;
            if (!is_array($options)) {
                $options = ['type' => $options];
            }
            $args['validators'] = $factory->create($options, $this->getContext());
        }
        return new IsArrayValidator($args);
    }
}
