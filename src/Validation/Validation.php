<?php
namespace PhalconX\Validation;

use Phalcon\Cache;
use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use PhalconX\Annotation\Annotations;
use PhalconX\Exception\ValidationException;
use PhalconX\Forms\Annotations\InputInterface;
use PhalconX\Forms\Annotations\Label;
use PhalconX\Validation\Annotations\ValidatorInterface;
use PhalconX\Validation\Annotations\Required;

/**
 * Allows to validate data using annotations
 */
class Validation implements InjectionAwareInterface
{
    /**
     * @var Annotations $annotations
     */
    private $annotations;

    /**
     * @var Cache\BackendInterface $cache
     */
    private $cache;

    /**
     * @var Phalcon\Logger\Adapter
     */
    private $logger;
    /**
     * @var DiInterface
     */
    private $di;

    /**
     * @var string default form class
     */
    private static $FORM_CLASS = 'Phalcon\Forms\Form';

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
     * Validate model object
     *
     * @param object|array $model
     * @param array $validators
     * @throws ValidationException
     */
    public function validate($model, $validators = null)
    {
        if (isset($validators) && is_array($validators)) {
            $validation = $this->validateArray($model, $validators);
        } else {
            $validation = $this->validateModel($model);
        }
        if ($validation) {
            $errors = $validation->validate($model);
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
    public function createForm($model, $formClass = null)
    {
        if (is_string($model)) {
            return $this->createFormInternal($model, new $model, $formClass);
        } else {
            return $this->createFormInternal(get_class($model), $model, $formClass);
        }
    }

    private function createFormInternal($modelClass, $modelObject, $formClass)
    {
        $annotations = $this->getAnnotations()->get($modelClass);
        $validators = $this->getValidators($annotations, $modelClass);
        $elems = $this->getElements($annotations, $modelClass);
        if (empty($elems)) {
            throw new \InvalidArgumentException("Cannot find element annotations in '$modelClass'");
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
        $it = $this->getAnnotations()->filter($annotations)
            ->is(Label::class)
            ->onProperties();
        $labels = [];
        foreach ($it as $annotation) {
            $labels[$annotation->getPropertyName()] = $annotation->value;
        }
        return $labels;
    }

    private function validateModel($model)
    {
        $validation = new \Phalcon\Validation;
        $modelClass = get_class($model);
        $annotations = $this->getAnnotations()->get($modelClass);
        $propValidators = $this->getValidators($annotations, $modelClass);
        if (empty($propValidators)) {
            $this->getLogger()->warning("Cannot find any validator annotations in '$modelClass'");
        } else {
            $validation->setLabels($this->getLabels($annotations));
            foreach ($propValidators as $property => $fieldValidators) {
                $value = $this->getValue($model, $property);
                if ($fieldValidators['required'] || $this->hasValue($value)) {
                    $validation->rules($property, $fieldValidators['validators']);
                }
            }
        }
        return $validation;
    }

    private function validateArray($model, $validators)
    {
        $factory = new ValidatorFactory($this);
        foreach ($validators as $field => $options) {
            $validation->rules($field, $factory->create($options));
        }
        return $validation;
    }
    
    /**
     * Gets all validators
     *
     * @return array
     */
    private function getValidators($annotations, $class)
    {
        $validators = $this->getCache()->get('_PHX.validators.' . $class);
        if (!isset($validators)) {
            $validators = [];
            $it = $this->getAnnotations()->filter($annotations)
                ->is(ValidatorInterface::class)
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
            $this->cache->save('_PHX.validators.'.$class, $validators);
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
        $it = $this->getAnnotations()->filter($annotations)
            ->is(InputInterface::class)
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
     * @return logger
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = $this->getAnnotations()->getLogger();
        }
        return $this->logger;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Gets the annotations component
     *
     * @return Annotations
     */
    public function getAnnotations()
    {
        if ($this->annotations === null) {
            $this->annotations = $this->getDi()->getAnnotations();
        }
        return $this->annotations;
    }

    public function setAnnotations(Annotations $annotations)
    {
        $this->annotations = $annotations;
        return $this;
    }

    /**
     * Gets the cache component
     *
     * @return \Phalcon\Cache\BackendInterface
     */
    public function getCache()
    {
        if ($this->cache === null) {
            $this->cache = $this->getAnnotations()->getCache();
        }
        return $this->cache;
    }

    public function setCache(Cache\BackendInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function getDi()
    {
        if ($this->di === null) {
            $this->di = Di::getDefault();
        }
        return $this->di;
    }

    public function setDi(DiInterface $di)
    {
        $this->di = $di;
        return $this;
    }
}
