<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Exception;
use PhalconX\Validation\Validators\IsA as IsAValidator;
use PhalconX\Annotation\Annotation;
use PhalconX\Validation\Form;
use PhalconX\Helper\ClassResolver;

class IsA extends Annotation implements ValidatorInterface
{
    protected static $DEFAULT_PROPERTY = 'class';
    
    public $class;
    
    public $message;

    public function getValidator(Form $form)
    {
        if (!$this->class) {
            throw new Exception("Class should be set");
        }
        $class = (new ClassResolver($form->getCache()))
            ->resolve($this->class, $this->getDeclaringClass());
        if (!$class) {
            throw new Exception(sprintf(
                "Class '%s' is not imported in %s",
                $this->class,
                $this->getDeclaringClass()
            ));
        }
        return new IsAValidator($form, [
            'class' => $class,
            'message' => $this->message
        ]);
    }
}
