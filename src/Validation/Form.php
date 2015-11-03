<?php
namespace PhalconX\Validation;

use Phalcon\Cache\BackendInterface as Cache;
use Phalcon\Validation;
use PhalconX\Annotation\Annotations;
use PhalconX\Exception\Exception;
use PhalconX\Exception\ValidationException;
use PhalconX\Forms\Annotations\InputInterface as Input;
use PhalconX\Forms\Annotations\Label;
use PhalconX\Validation\Annotations\ValidatorInterface as Validator;
use PhalconX\Validation\Annotations\Required;

class Form
{
    /**
     * @var Annotations $annotations
     */
    private $annotations;

    /**
     * @var Cache $cache
     */
    private $cache;

    /**
     * @var Phalcon\Logger\Adapter
     */
    private $logger;

    /**
     * @var string default form class
     */
    private static $FORM_CLASS = 'Phalcon\Forms\Form';

    /**
     * Constructor.
     *
     * @param Annotations $annotations
     */
    public function __construct(Cache $cache = null, $logger = null, Annotations $annotations = null)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->annotations = $annotations ?: new Annotations();
    }

    /**
     * Sets the default form class
     *
     * @param string
     */
    public static function setFormClass($formClass)
    {
        self::$FORM_CLASS = $formClass;
    }

    /**
     * Gets the default form class
     *
     * @return string
     */
    public static function getFormClass()
    {
        return self::$FORM_CLASS;
    }
    
    /**
     * Validate form object
     *
     * @param object|array $model
     * @throws ValidationException
     */
    public function validate($form)
    {
        $validation = new Validation;
        $formClass = get_class($form);
        $annotations = $this->annotations->get($formClass);
        $propValidators = $this->getValidators($annotations, $formClass);
        if (empty($propValidators)) {
            if ($this->logger) {
                $this->logger->warning("Cannot find any validator annotations in '$formClass'");
            }
        } else {
            $validation->setLabels($this->getLabels($annotations));
            foreach ($propValidators as $property => $fieldValidators) {
                $value = $this->getValue($form, $property);
                if ($fieldValidators['required'] || $this->hasValue($value)) {
                    $validation->rules($property, $fieldValidators['validators']);
                }
            }
            $errors = $validation->validate($form);
            if (count($errors)) {
                throw new ValidationException($errors);
            }
        }
    }

    /**
     * Create form object
     *
     * @param string|object $model model class or object
     * @param string $formClass form class, default use Phalcon\Forms\Form
     * @return Phalcon\Forms\Form
     */
    public function create($model, $formClass = null)
    {
        if (is_string($model)) {
            return $this->createFormInternal($model, new $model, $formClass);
        } else {
            return $this->createFormInternal(get_class($model), $model, $formClass);
        }
    }

    private function createFormInternal($modelClass, $modelObject, $formClass)
    {
        $annotations = $this->annotations->get($modelClass);
        $validators = $this->getValidators($annotations, $modelClass);
        $elems = $this->getElements($annotations, $modelClass);
        if (empty($elems)) {
            throw new Exception("Cannot find element annotations in '$modelClass'");
        }
        if (!$formClass) {
            $formClass = self::$FORM_CLASS;
        }
        $form = new $formClass();
        foreach ($elems as $property => $elem) {
            $form->add($elem);
        }
        $form->bind((array) $modelObject, $modelObject);
        
        foreach ($elems as $property => $elem) {
            if (isset($validators[$property])) {
                $value = $form->getValue($elem->getName());
                if ($validators[$property]['required'] || $this->hasValue($value)) {
                    $elem->addValidators($validators[$property]['validators']);
                }
            }
        }
        return $form;
    }

    /**
     * Gets property labels
     *
     * @return array
     */
    private function getLabels($annotations)
    {
        $it = $this->annotations->filter($annotations)
            ->is(Label::class)
            ->onProperties();
        $labels = [];
        foreach ($it as $annotation) {
            $labels[$annotation->getPropertyName()] = $annotation->value;
        }
        return $labels;
    }
    
    /**
     * Gets all validators
     *
     * @return array
     */
    private function getValidators($annotations, $class)
    {
        if ($this->cache) {
            $validators = $this->cache->get('__PHX.validators.' . $class);
            if (isset($validators)) {
                return $validators;
            }
        }
        $validators = [];
        $it = $this->annotations->filter($annotations)
            ->is(Validator::class)
            ->onProperties();
        foreach ($it as $annotation) {
            $property = $annotation->getPropertyName();
            if (!isset($validators[$property])) {
                $validators[$property] = [
                    'required' => false,
                    'validators' => [],
                ];
            }
            $validators[$property]['validators'][] = $annotation->getValidator($this);
            if ($annotation instanceof Required) {
                $validators[$property]['required'] = true;
            }
        }
        if ($this->cache) {
            $this->cache->save('__PHX.validators.'.$class, $validators);
        }
        return $validators;
    }

    /**
     * Gets all form elements
     *
     * @return array key is property, value is Phalcon\Form\Element object
     */
    private function getElements($annotations, $formClass)
    {
        $labels = $this->getLabels($annotations);
        $reflection = new \ReflectionClass($formClass);
        $defaultValues = $reflection->getDefaultProperties();

        $elements = [];
        $it = $this->annotations->filter($annotations)
            ->is(Input::class)
            ->onProperties();
        foreach ($it as $annotation) {
            $property = $annotation->getPropertyName();
            if (!isset($annotation->name)) {
                $annotation->name = $property;
            }
            $elem = $annotation->getElement($this);
            if ($annotation->label) {
                $label = $annotation->label;
            } elseif (isset($labels[$property])) {
                $label = $labels[$property];
            } else {
                $label = str_replace('_', ' ', ucfirst($property));
            }
            $elem->setLabel($label);
            if (isset($defaultValues[$property])) {
                $elem->setDefault($defaultValues[$property]);
            }
            $elements[$property] = $elem;
        }
        return $elements;
    }

    /**
     * Gets the value in the array/object data source
     *
     * @return mixed
     */
    private function getValue($form, $name)
    {
        if (is_array($form)) {
            return isset($form[$name]) ? $form[$name] : null;
        } elseif (is_object($form)) {
            $method = 'get' . $name;
            if (method_exists($form, $method)) {
                return $form->$method();
            } else {
                return isset($form->$name) ? $form->$name : null;
            }
        }
    }
    
    /**
     * Checks whether value is empty
     *
     * @return bool true if not empty
     */
    private function hasValue($value)
    {
        return isset($value) && $value !== '';
    }

    /**
     * Gets the annotations component
     *
     * @return Annotations
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * Gets the cache component
     *
     * @return \Phalcon\Cache\BackendInterface
     */
    public function getCache()
    {
        return $this->cache;
    }
}
