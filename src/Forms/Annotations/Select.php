<?php
namespace PhalconX\Forms\Annotations;

use Phalcon\Forms\Element\Select as SelectElement;
use Phalcon\Mvc\Model;
use PhalconX\Enum\Enum;
use PhalconX\Exception\BadAnnotationException;
use PhalconX\Validation\Validation;
use PhalconX\Helper\ClassResolver;

/**
 * select
 *
 * <example>
 * use PhalconX\Forms\Annotations\Select;
 *
 * @Select(options = ['yes', 'no'])
 *
 * @Select(model = Enum)
 *
 * @Select(model = Model, using = ['id', 'name'])
 * </example>
 */
class Select extends Input
{
    protected static $elementClass = SelectElement::class;
    
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

    protected function getOptions(Validation $form)
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
        throw new BadAnnotationException($this, "model or options parameter is required");
    }
    
    public function getElement(Validation $form)
    {
        return new static::$elementClass($this->name, $this->getOptions($form), $this->getAttributes());
    }
}
