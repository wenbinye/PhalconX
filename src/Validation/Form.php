<?php
namespace PhalconX\Validation;

use Phalcon\Cache\BackendInterface as Cache;
use Phalcon\Validation;
use PhalconX\Annotation\Annotations;
use PhalconX\Exception\Exception;
use PhalconX\Exception\ValidationException;
use PhalconX\Forms\Form as PhalconForm;
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
     * @param string|object $form Form class or form object
     * @return Phalcon\Forms\Form
     */
    public function create($form)
    {
        if (is_string($form)) {
            return $this->createFormInternal($form, new $form);
        } else {
            return $this->createFormInternal(get_class($form), $form);
        }
    }

    private function createFormInternal($formClass, $formObject)
    {
        $annotations = $this->annotations->get($formClass);
        $validators = $this->getValidators($annotations, $formClass);
        $elems = $this->getElements($annotations, $formClass);
        if (empty($elems)) {
            throw new Exception("Cannot find element annotations in '$formClass'");
        }
        
        $form = new PhalconForm();
        foreach ($elems as $property => $elem) {
            $form->add($elem);
        }
        $form->bind((array) $formObject, $formObject);
        
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
            $elem = $annotation->getElement();
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
