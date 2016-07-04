<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Exception;
use PhalconX\Annotation\Annotation;
use PhalconX\Validation\Validation;
use PhalconX\Helper\ClassResolver;

class ValidateBy extends Annotation implements ValidatorInterface
{
    protected static $DEFAULT_PROPERTY = 'type';
    
    public $type;

    protected $_args;

    protected function assign($args)
    {
        foreach ($args as $name => $val) {
            if (property_exists($this, $name)) {
                $this->$name = $val;
            } else {
                $this->_args[$name] = $val;
            }
        }
    }
    
    public function getValidator(Validation $validation)
    {
        if (!$this->type) {
            throw new Exception("Validator type should be set");
        }
        $class = (new ClassResolver($validation->getCache()))
            ->resolve($this->type, $this->getDeclaringClass());
        if (!$class) {
            throw new Exception(sprintf(
                "Class '%s' is not imported in %s",
                $this->type,
                $this->getDeclaringClass()
            ));
        }
        return new $class($this->_args);
    }
}
