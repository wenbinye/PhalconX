<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Select as SelectElement;
use Phalcon\Mvc\Model;
use PhalconX\Enum\Enum;
use PhalconX\Validation\Form;
use PhalconX\Helper\ClassResolver;

class Select extends Input
{
    /**
     * @var string enum class for domain
     */
    public $model;

    /**
     * @var array arguments for model::find
     */
    public $criteria;

    /**
     * @var array select options label => value pair
     */
    public $options;

    protected function getOptions(Form $form)
    {
        if (is_array($this->options)) {
            return $this->options;
        }
        $attributes = $this->getAttributes();
        if ($this->model) {
            $modelClass = (new ClassResolver($form->getCache()))
                ->resolve($this->model, $this->getDeclaringClass());
            if (is_subclass_of($modelClass, Enum::class)) {
                $options = [];
                foreach ($modelClass::instances() as $enum) {
                    $options[$enum->value] = $enum->description;
                }
                return $options;
            } elseif (is_subclass_of($modelClass, Model::class)) {
                if (empty($attributes['using'])) {
                    throw new \InvalidArgumentException("The 'using' parameter is required for " . $this);
                }
                $this->criteria['columns'] = $attributes['using'];
                return $modelClass::find($this->criteria);
            }
        }
        throw new \InvalidArgumentException();
    }
    
    public function getElement(Form $form)
    {
        return new SelectElement($this->name, $this->getOptions($form), $this->getAttributes());
    }
}