<?php
namespace PhalconX;

use Phalcon\DI\Injectable;
use Phalcon\Validation;
use PhalconX\Exception\ValidationException;
use PhalconX\Forms\Form;
use PhalconX\Annotations\Validator\Validator as ValidatorAnnotation;
use PhalconX\Annotations\ContextType;
use PhalconX\Annotations\Forms\Input;
use Phalcon\Mvc\Model;

class Validator extends Injectable
{
    /**
     * Validates the form
     *
     * @param object|array $model
     * @param Validators[] $validators
     * @throws ValidationException
     */
    public function validate(&$model, $validators = null)
    {
        if (!isset($validators) && is_object($model)) {
            $validators = [];
            $propValidators = $this->getPropertyValidators(get_class($model));
            foreach ($propValidators as $name => $val) {
                $validators = array_merge($validators, $val);
            }
        }
        $names = [];
        foreach ($validators as $validator) {
            $names[$validator->name] = 1;
        }
        if (is_array($model)) {
            $data = &$model;
        } elseif ($model instanceof Model) {
            $data = $model->toArray();
        } else {
            $data = [];
            foreach ($names as $name => $i) {
                if (isset($model->$name)) {
                    $data[$name] = &$model->$name;
                }
            }
        }
        $validation = new Validation;
        foreach ($validators as $validator) {
            $name = $validator->name;
            if (!$this->hasValue($data, $name) && isset($validator->default)) {
                $data[$name] = $validator->default;
            }
            // 当字段为必须提供或者值存在
            if ($validator->required || $this->hasValue($data, $name)) {
                foreach ($validator->process() as $v) {
                    $validation->add($name, $v);
                }
            }
        }
        $errors = $validation->validate($data);
        if (count($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function hasValue($data, $name)
    {
        return isset($data[$name]) && $data[$name] !== '';
    }
    
    /**
     * 创建表单对象
     * @param string|object $model
     * @param array $bind
     * @return \Phalcon\Forms\Form
     */
    public function createForm($model, $bind = null)
    {
        if (is_string($model)) {
            $clz = $model;
            $model = new $clz;
        } else {
            $clz = get_class($model);
            $bind = (array) $model;
        }
        $form = new Form;
        $validators = $this->getPropertyValidators($clz);
        foreach ($this->getElements($clz) as $name => $elem) {
            if (isset($validators[$name])) {
                foreach ($validators[$name] as $validator) {
                    $elem->setDefault($validator->default);
                    if ($validator->required || $this->hasValue($model, $name)) {
                        $elem->addValidators($validator->process());
                    }
                }
            }
            $form->add($elem);
        }
        if ($bind) {
            $form->bind($bind, $model);
            $form->setEntity($model);
        }
        return $form;
    }

    /**
     * 提取类属性验证器
     * @param string $clz
     * @return [ propertyName => Validator[] ]
     */
    private function getPropertyValidators($clz)
    {
        $refl = new \ReflectionClass($clz);
        $defaults = $refl->getDefaultProperties();
        $properties = [];
        $annotations = $this->annotations->getAnnotations($clz, ValidatorAnnotation::CLASS, ContextType::T_PROPERTY);
        foreach ($annotations as $annotation) {
            $property = $annotation->getProperty();
            $annotation->name = $property;
            if (!isset($annotation->default) && isset($defaults[$property])) {
                $annotation->default = $defaults[$property];
            }
            if (!isset($properties[$property])) {
                $properties[$property] = [];
            }
            $annotation->setAnnotations($this->annotations);
            $properties[$property][] = $annotation;
        }
        return $properties;
    }
    
    private function getElements($clz)
    {
        $elements = [];
        $annotations = $this->annotations->getAnnotations($clz, Input::CLASS, ContextType::T_PROPERTY);
        foreach ($annotations as $annotation) {
            if (!isset($annotation->name)) {
                $annotation->name = $annotation->getProperty();
            }
            $elements[$annotation->getProperty()] = $annotation->process();
        }
        return $elements;
    }
}
