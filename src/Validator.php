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
use PhalconX\Validators\Boolean;
use PhalconX\Validators\Max;
use PhalconX\Validators\Min;
use PhalconX\Validators\StringLength;
use PhalconX\Validators\EitherPresenceOf;

class Validator extends Injectable
{
    private $annotationName = 'Valid';
    private $validators = [
        'Max' => Max::CLASS,
        'Min' => Min::CLASS,
        'StringLength' => StringLength::CLASS,
        'Boolean' => Boolean::CLASS,
        'EitherPresenceOf' => EitherPresenceOf::CLASS,
    ];
    private $types;
    
    public function __construct($options = null)
    {
        $is_int = new Generic(['validate' => function ($value, $validator) {
                    return ctype_digit($value) || is_int($value);
        }]);
        $this->types = [
            'integer' => $is_int,
            'number' => Numericality::CLASS,
            'array' => IsArray::CLASS,
            'boolean' => Boolean::CLASS,
            'int_array' => new IsArray(['element' => $is_int]),
            'string' => new Generic(['validate' => function ($value, $validator) {
                        return is_string($value);
            }])
        ];
        if (is_array($options)) {
            if (isset($options['annotation'])) {
                $this->annotationName = $options['annotation'];
            }
            if (isset($options['types'])) {
                $this->types = array_merge($this->types, $options['types']);
            }
            if (isset($options['validators'])) {
                $this->validators = array_merge($this->validators, $options['validators']);
            }
        }
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
        foreach ($form as &$elem) {
            if ((!isset($elem['value']) || $elem['value'] == '') && isset($elem['default'])) {
                $elem['value'] = $elem['default'];
            }
            if (isset($elem['value'])) {
                $data[$elem['name']] = $elem['value'];
            }
            if (!empty($elem['required'])) {
                $validation->add($elem['name'], new PresenceOf());
            }
            if (isset($elem['type'])) {
                if (!isset($this->types[$elem['type']])) {
                    throw new \UnexpectedValueException("Cannot handle type {$elem['type']} for field {$elem['name']}");
                }
                $validation->add($elem['name'], $this->createValidator($this->types[$elem['type']]));
            }
            if (isset($elem['validator'])) {
                $validation->add($elem['name'], $this->createValidator($elem['validator']));
            }
        }
        $errors = $validation->validate($data);
        if (count($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function getAnnotations($form)
    {
        $properties = $this->annotations->getProperties(get_class($form));
        $validators = [];
        
        foreach ($properties as $name => $annotations) {
            unset($value);
            $value = &$form->$name;
            foreach ($annotations as $annotation) {
                if ($annotation->getName() != $this->annotationName) {
                    continue;
                }
                $validator = $annotation->getArguments();
                $validator['name'] = $name;
                if (isset($validator['default'])) {
                    $validator['value'] = &$value;
                } else {
                    $validator['value'] = $value;
                }
                if (isset($validator['validator'])) {
                    $validator['validator'] = $this->createValidatorFromAnnotation($validator['validator']);
                }
                $validators[] = $validator;
            }
        }
        return $validators;
    }

    private function createValidator($validator)
    {
        if ($validator instanceof ValidatorInterface) {
            return $validator;
        }
        return new $validator();
    }
    
    private function createValidatorFromAnnotation($annotation)
    {
        $name = $annotation->getName();
        if (isset($this->validators[$name])) {
            $class = $this->validators[$name];
        } else {
            $class = 'Phalcon\Validation\Validator\\' . $name;
        }
        return new $class($annotation->getArguments());
    }
}
