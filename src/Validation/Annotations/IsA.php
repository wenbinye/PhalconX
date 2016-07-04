<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Exception;
use PhalconX\Validation\Validators\IsA as IsAValidator;
use PhalconX\Annotation\Annotation;
use PhalconX\Validation\Validation;
use PhalconX\Helper\ClassResolver;

class IsA extends Annotation implements ValidatorInterface
{
    protected static $DEFAULT_PROPERTY = 'class';
    
    public $class;
    
    public $message;

    public function getValidator(Validation $validation)
    {
        if (!$this->class) {
            throw new Exception("Class should be set");
        }
        $class = $this->class;
        if ($this->getDeclaringClass()) {
            $class = (new ClassResolver($validation->getCache()))
                   ->resolve($class, $this->getDeclaringClass());
            if (!$class) {
                throw new Exception(sprintf(
                    "Class '%s' is not imported in %s",
                    $this->class,
                    $this->getDeclaringClass()
                ));
            }
        }
        return new IsAValidator($validation, [
            'class' => $class,
            'message' => $this->message
        ]);
    }
}
