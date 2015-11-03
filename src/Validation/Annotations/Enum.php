<?php
namespace PhalconX\Validation\Annotations;

use Phalcon\Validation\Validator\InclusionIn;
use Phalcon\Mvc\Model;
use Phalcon\Text;
use PhalconX\Annotation\Annotation;
use PhalconX\Validation\Form;
use PhalconX\Enum\Enum as EnumType;
use PhalconX\Helper\ClassResolver;
use PhalconX\Validation\Validators\InclusionInModel;

class Enum extends Annotation implements ValidatorInterface
{
    protected static $DEFAULT_PROPERTY = 'model';

    /**
     * @var array|string
     */
    public $model;

    /**
     * @var array
     */
    public $domain;

    /**
     * @var string model property name
     */
    public $attribute;

    /**
     * @var string error message
     */
    public $message;

    public function getValidator(Form $form)
    {
        if (is_array($this->model) || is_array($this->domain)) {
            return new InclusionIn([
                'domain' => is_array($this->model) ? $this->model : $this->domain,
                'message' => $this->message
            ]);
        } elseif ($this->model) {
            $useEnumValues = false;
            $modelClass = $this->model;
            if (Text::endsWith($modelClass, '.values')) {
                $modelClass = substr($modelClass, 0, -7);
                $useEnumValues = true;
            }
            $classResolver = new ClassResolver($form->getCache());
            $modelClass = $classResolver->resolve($modelClass, $this->getDeclaringClass());
            if ($modelClass) {
                if (is_subclass_of($modelClass, EnumType::class)) {
                    if ($useEnumValues) {
                        $domain = $modelClass::values();
                    } else {
                        $domain = array_map('strtolower', $modelClass::names());
                    }
                    return new InclusionIn([
                        'domain' => $domain,
                        'message' => $this->message
                    ]);
                } elseif (is_subclass_of($modelClass, Model::class)) {
                    return new InclusionInModel([
                        'model' => $modelClass,
                        'attribute' => $this->attribute,
                        'message' => $this->message
                    ]);
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
