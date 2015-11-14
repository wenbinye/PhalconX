<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;
use PhalconX\Validation\Form;
use PhalconX\Helper\ClassResolver;

class Uniqueness extends Validator
{
    protected static $validatorClass = UniquenessValidator::class;

    protected static $DEFAULT_PROPERTY = 'model';

    /**
     * @var string model class
     */
    public $model;

    /**
     * @var string model attribute name
     */
    public $attribute;

    /**
     * @var mixed except value
     */
    public $except;

    /**
     * @var string error message
     */
    public $message;

    public function getValidator(Form $form)
    {
        $context = $this->getContext();
        if ($context->getDeclaringClass()) {
            $modelClass = (new ClassResolver($form->getCache()))
                ->resolve($this->model, $context->getDeclaringClass());
            if ($modelClass) {
                $this->model = $modelClass;
            }
        }
        return parent::getValidator($form);
    }
}
