<?php
namespace PhalconX;

use Phalcon\DI\Injectable;
use Phalcon\Validation;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Validator\Numericality;
use Phalcon\Validation\Validator\PresenceOf;
use PhalconX\Exception\ValidationException;
use PhalconX\Validators\Generic;
use PhalconX\Validators\Integer;
use PhalconX\Validators\IsArray;
use PhalconX\Validators\IsA;
use PhalconX\Validators\Boolean;
use PhalconX\Validators\Range;
use PhalconX\Validators\StringLength;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\Date;
use Phalcon\Forms\Element\Email;
use Phalcon\Forms\Element\File;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Numeric;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Radio;
use Phalcon\Forms\Element\Select;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\TextArea;

class Validator extends Injectable
{
    private $validAnnotationName = 'Valid';
    private $isaAnnotationName = 'IsA';
    private $types;
    private $elements = [
        'Check' => Check::CLASS,
        'Date' => Date::CLASS,
        'Email' => Email::CLASS,
        'File' => File::CLASS,
        'Hidden' => Hidden::CLASS,
        'Numeric' => Numeric::CLASS,
        'Password' => Password::CLASS,
        'Radio' => Radio::CLASS,
        'Select' => Select::CLASS,
        'Submit' => Submit::CLASS,
        'Text' => Text::CLASS,
        'TextArea' => TextArea::CLASS,
    ];

    private $annotations;
    private $reflection;
    private $modelsMetadata;
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
            if (isset($options['formElements'])) {
                $this->elements = array_merge($this->elements, $options['formElements']);
            }
        }
        $this->annotations = Util::service('annotations', $options);
        $this->reflection = Util::service('reflection', $options);
        $this->modelsMetadata = Util::service('modelsMetadata', $options, false);
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
     *  when type equals integer or number
     *  - maximum
     *  - minimum
     *  when type equals string
     *  - maxLength
     *  - minLength
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
            $validators = $this->toValidators($elem);
            if (isset($elem['value'])) {
                $data[$name] = $elem['value'];
            }
            foreach ($validators as $validator) {
                $validation->add($name, $validator);
            }
        }
        $errors = $validation->validate($data);
        if (count($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function toValidators(&$elem)
    {
        $validators = [];
        if ((!isset($elem['value']) || $elem['value'] == '') && isset($elem['default'])) {
            $elem['value'] = $elem['default'];
        }
        if (!empty($elem['required'])) {
            $validators[] = new PresenceOf();
        }
        if (isset($elem['value'])) {
            if (isset($elem['type'])) {
                if (!isset($this->types[$elem['type']])) {
                    if ($this->logger) {
                        $this->logger->error("Cannot handle type {$elem['type']} for field {$name}");
                    }
                } else {
                    $validators[] = $this->createTypeValidator($elem);
                    // 处理 string, integer, number 类型 min, max 属性
                    if (in_array($elem['type'], ['integer', 'number'])) {
                        if (isset($elem['maximum']) || isset($elem['minimum'])) {
                            $validators[] = new Range($elem);
                        }
                    } elseif ($elem['type'] == 'string') {
                        if (isset($elem['maxLength']) || isset($elem['minLength'])) {
                            $validators[] = new StringLength([
                                'max' => isset($elem['maxLength']) ? $elem['maxLength'] : null,
                                'min' => isset($elem['minLength']) ? $elem['minLength'] : null
                            ]);
                        }
                    }
                }
            }
            if (isset($elem['validator'])) {
                $validators[] = $this->createValidator($elem['validator'], null);
            }
        }
        return $validators;
    }
    
    /**
     * 创建表单对象
     * @return \Phalcon\Forms\Form
     */
    public function createForm($model)
    {
        $form = new Form;
        $clz = is_string($model) ? $model : get_class($model);
        $validators = $this->getPropertyValidators($clz);
        foreach ($this->getElements($clz) as $name => $elem) {
            if (isset($validators[$name])) {
                $elem->addValidators($this->toValidators($validators[$name]));
            }
            $form->add($elem);
        }
        return $form;
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
        if ($this->modelsMetadata) {
            $validators = $this->modelsMetadata->read($clz.'.validators');
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
            if ($this->modelsMetadata) {
                $this->modelsMetadata->write($clz.'.validators', $validators);
            }
        }
        return $validators;
    }

    private function addTypeValidator($validation, $name, $args)
    {
    }
    
    private function createValidator($validator, $args = null)
    {
        if ($validator instanceof ValidatorInterface) {
            return $validator;
        }
        return $this->getDi()->get($validator, [$args]);
    }

    private function createTypeValidator($args)
    {
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
            return $this->createValidator($class, $annotation->getArguments());
        }
    }

    private function getElements($clz)
    {
        if ($this->modelsMetadata) {
            $elements = $this->modelsMetadata->read($clz.'.formElements');
        }
        if (!isset($elements)) {
            $elements = [];
            foreach ($this->annotations->getProperties($clz) as $name => $annotations) {
                foreach ($annotations as $annotation) {
                    if (isset($this->elements[$annotation->getName()])) {
                        $elements[$name] = [$annotation->getName(), $annotation->getArguments()];
                    }
                }
            }
            if ($this->logger) {
                $this->logger->info("Parse form elements from class " . $clz);
            }
            if ($this->modelsMetadata) {
                $this->modelsMetadata->write($clz.'.formElements', $elements);
            }
        }
        foreach ($elements as $name => $element) {
            $elements[$name] = $this->createElement($name, $element[0], $element[1]);
        }
        return $elements;
    }
    
    private function createElement($name, $elementType, $args)
    {
        $clz = $this->elements[$elementType];
        if (isset($args[0])) {
            array_unshift($args, $name);
            $refl = new \ReflectionClass($clz);
            return $refl->newInstanceArgs($args);
        } else {
            return new $clz($name, $args);
        }
    }
}
