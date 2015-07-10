<?php
namespace PhalconX;

use Phalcon\DI\Injectable;
use Phalcon\Validation;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Validator\Numericality;
use Phalcon\Validation\Validator\PresenceOf;
use PhalconX\Validators\Generic;
use PhalconX\Validators\Integer;
use PhalconX\Validators\IsArray;
use PhalconX\Validators\IsA;
use PhalconX\Validators\Boolean;
use PhalconX\Validators\StringLength;

class Validator extends Injectable
{
    private $validAnnotationName = 'Valid';
    private $isaAnnotationName = 'IsA';
    private $types;

    private $annotations;
    private $reflection;
    private $cache;
    private $logger;

    /**
     * Construct the validator
     *
     * Available options:
     *  - annotation
     *  - types
     *  - validators
     *  - annotations
     *  - reflection
     */
    public function __construct($options = null)
    {
        $this->types = [
            'boolean' => Boolean::CLASS,
            'integer' => Integer::CLASS,
            'number' => Numericality::CLASS,
            'array' => IsArray::CLASS,
            'string' => new Generic([
                'validate' => function ($value, $validator) {
                    return is_string($value);
                }
            ])
        ];
        if (is_array($options)) {
            if (isset($options['validAnnotation'])) {
                $this->validAnnotationName = $options['validAnnotation'];
            }
            if (isset($options['isaAnnotation'])) {
                $this->isaAnnotationName = $options['isaAnnotation'];
            }
            if (isset($options['types'])) {
                $this->types = array_merge($this->types, $options['types']);
            }
            if (isset($options['validators'])) {
                $this->validators = array_merge($this->validators, $options['validators']);
            }
        }
        $this->annotations = Util::service('annotations', $options);
        $this->reflection = Util::service('reflection', $options);
        $this->cache = Util::service('cache', $options, false);
        $this->logger = Util::service('logger', $options, false);
    }

    /**
     * Validates the form
     *
     * Form may be an array of specifications:
     *  - name
     *  - type
     *  - value
     *  - default
     *  - required
     *  - validator
     */
    public function validate($form)
    {
        if (!is_array($form)) {
            return $this->validate($this->getAnnotations($form));
        }
        $validation = new Validation;
        $data = [];
        unset($elem);
        foreach ($form as &$elem) {
            if (!isset($elem['name'])) {
                throw new \UnexpectedValueException("Validator should contain a name");
            }
            $name = $elem['name'];
            if ((!isset($elem['value']) || $elem['value'] == '') && isset($elem['default'])) {
                $elem['value'] = $elem['default'];
            }
            if (isset($elem['value'])) {
                $data[$name] = $elem['value'];
            }
            if (!empty($elem['required'])) {
                $validation->add($name, new PresenceOf());
            }
            if (isset($elem['value'])) {
                if (isset($elem['type'])) {
                    $validation->add($name, $this->createTypeValidator($elem));
                }
                if (isset($elem['validator'])) {
                    $validation->add($name, $this->createValidator($elem['validator'], null));
                }
            }
        }
        $errors = $validation->validate($data);
        if (count($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function getAnnotations($form)
    {
        $clz = get_class($form);
        $properties = $this->getPropertyValidators($clz);
        $validators = [];
        foreach ($properties as $name => $propValidators) {
            unset($value);
            $value = &$form->$name;
            foreach ($propValidators as $validator) {
                if (isset($validator['default'])) {
                    $validator['value'] = &$value;
                } else {
                    $validator['value'] = $value;
                }
                $validators[] = $validator;
            }
        }
        return $validators;
    }

    private function getPropertyValidators($clz)
    {
        if ($this->cache) {
            $validators = $this->cache->get($clz.'.validators');
        }
        if (!isset($validators)) {
            $properties = $this->annotations->getProperties($clz);
            $validators = [];
        
            foreach ($properties as $name => $annotations) {
                $propValidators = [];
                foreach ($annotations as $annotation) {
                    $annoName = $annotation->getName();
                    if ($annoName == $this->isaAnnotationName) {
                        $validator = ['name' => $name];
                        $validator['validator'] = $this->createIsaValidator($annotation, $clz);
                        $propValidators[] = $validator;
                    } elseif ($annoName == $this->validAnnotationName) {
                        $validator = $annotation->getArguments();
                        $validator['name'] = $name;
                        if (isset($validator['validator'])) {
                            $validator['validator'] = $this->createAnnoValidator($validator['validator'], $clz);
                        }
                        if (isset($validator['type']) && $validator['type'] === 'array'
                            && isset($validator['element'])) {
                            $validator['element'] = $this->createAnnoValidator($validator['element'], $clz);
                        }
                        $propValidators[] = $validator;
                    }
                }
                if ($propValidators) {
                    $validators[$name] = $propValidators;
                }
            }
            if ($this->logger) {
                $this->logger->info("Parse validators from class " . $clz);
            }
            if ($this->cache) {
                $this->cache->save($clz.'.validators', $validators);
            }
        }
        return $validators;
    }
    
    private function createValidator($validator, $args = null)
    {
        if ($validator instanceof ValidatorInterface) {
            return $validator;
        }
        return new $validator($args);
    }

    private function createTypeValidator($args)
    {
        if (!isset($this->types[$args['type']])) {
            throw new \UnexpectedValueException("Cannot handle type {$elem['type']} for field {$name}");
        }
        if ($args['type'] === 'array' && isset($args['elementType'])) {
            $args['element'] = $this->createTypeValidator(['type' => $args['elementType']]);
        }
        return $this->createValidator($this->types[$args['type']], $args);
    }
    
    private function createIsaValidator($annotation, $clz)
    {
        $args = $annotation->getArguments();
        $type = isset($args['class']) ? $args['class'] : $args[0];
        $isArray = false;
        if (strpos($type, '[]') !== false) {
            $type = substr($type, 0, -2);
            $isArray = true;
        }
        $typeClass = $this->reflection->resolveImport($type, $clz);
        if ($isArray) {
            $validator = new IsArray(['element' => new IsA(['class' => $typeClass])]);
        } else {
            $validator = new IsA(['class' => $typeClass]);
        }
        return $validator;
    }
    
    private function createAnnoValidator($annotation, $clz)
    {
        $name = $annotation->getName();
        $class = $this->reflection->resolveImport($name, $clz);
        if ($class === IsA::CLASS) {
            return $this->createAnnoValidator($annotation, $clz);
        } else {
            return new $class($annotation->getArguments());
        }
    }
}
