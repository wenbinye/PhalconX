<?php
namespace PhalconX;

use Phalcon\DI\Injectable;
use Phalcon\Validation;
use PhalconX\Exception\ValidationException;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Forms\Form;

use Phalcon\Validation\Validator\Numericality;
use Phalcon\Validation\Validator\PresenceOf;
use PhalconX\Validators\Generic;
use PhalconX\Validators\Integer;
use PhalconX\Validators\IsArray;
use PhalconX\Validators\IsA;
use PhalconX\Validators\Boolean;

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
     * @param object|array $form
     * @throws ValidationException
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
            $spec = new ValidatorSpec($elem, $this);
            // 根据默认值修改传入参数
            if (!$spec->hasValue() && isset($spec->default)) {
                $elem['value'] = $spec->default;
            }
            $data[$spec->name] = $spec->value;
            // 当字段为必须提供或者值存在
            if ($spec->required || $spec->hasValue()) {
                foreach ($spec->toValidators() as $validator) {
                    $validation->add($spec->name, $validator);
                }
            }
        }
        $errors = $validation->validate($data);
        if (count($errors)) {
            throw new ValidationException($errors);
        }
    }
    
    /**
     * 创建表单对象
     * @param string|object $model
     * @return \Phalcon\Forms\Form
     */
    public function createForm($model)
    {
        $form = new Form;
        $clz = is_string($model) ? $model : get_class($model);
        $validators = $this->getPropertyValidators($clz);
        foreach ($this->getElements($clz) as $name => $elem) {
            if (isset($validators[$name])) {
                foreach ($validators[$name] as $validator) {
                    if (is_object($model)) {
                        $elem->setDefault($model->$name);
                    } elseif (isset($validator['default'])) {
                        $elem->setDefault($validator['default']);
                    }
                    $validator['name'] = $name;
                    $spec = new ValidatorSpec($validator, $this);
                    $elem->addValidators($spec->toValidators());
                }
            }
            $form->add($elem);
        }
        return $form;
    }

    /**
     * 创建验证条件
     */
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
                $validator['name'] = $name;
                $validators[] = $validator;
            }
        }
        return $validators;
    }

    /**
     * 提取类属性验证器
     * @param string $clz
     * @return [ propertyName => [] ]
     */
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
                        $validator = ['validator' => $this->resolveIsA($annotation, $clz)];
                        $propValidators[] = $validator;
                    } elseif ($annoName == $this->validAnnotationName) {
                        $validator = $annotation->getArguments();
                        if (isset($validator['validator'])) {
                            $validator['validator'] = $this->resolveAnnotation($validator['validator'], $clz);
                        }
                        if (isset($validator['type']) && $validator['type'] === 'array'
                            && isset($validator['element'])) {
                            $validator['element'] = $this->resolveAnnotation($validator['element'], $clz);
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
        unset($propValidators);
        $result = [];
        foreach ($validators as $name => &$propValidators) {
            unset($validator);
            foreach ($propValidators as &$validator) {
                if (isset($validator['validator'])) {
                    $validator['validator'] =
                        $this->createValidator(
                            $validator['validator'][0],
                            $validator['validator'][1]
                        );
                } elseif (isset($validator['type']) && $validator['type'] === 'array'
                          && isset($validator['element'])) {
                    $validator['element'] = $this->createValidator(
                        $validator['element'][0],
                        $validator['element'][1]
                    );
                }
            }
        }
        return $validators;
    }

    /**
     * 创建 validator
     */
    public function createValidator($validator, $args = null)
    {
        if (is_array($validator) && isset($validator['type'])) {
            return $this->createTypeValidator($validator);
        }
        if ($validator instanceof ValidatorInterface) {
            return $validator;
        }
        if ($validator == IsArray::CLASS && isset($args['element'])) {
            return $this->createTypeValidator([
                'type' => 'array',
                'element' => $args['element']
            ]);
        }
        return $this->getDi()->get($validator, [$args]);
    }

    private function createTypeValidator($args)
    {
        if (!isset($args['type']) || !isset($this->types[$args['type']])) {
            if ($this->logger) {
                $this->logger->error("Cannot handle type {$args['type']}");
                return null;
            }
        }
        if ($args['type'] === 'array' && isset($args['element'])) {
            if (is_string($args['element'])) {
                $args['element'] = $this->createTypeValidator(['type' => $args['element']]);
            } elseif (is_array($args) && isset($args['class'])) {
                $args['element'] = new IsA($args);
            }
            return new IsArray($args);
        } else {
            return $this->createValidator($this->types[$args['type']], $args);
        }
    }

    /**
     * validator=@IsA()
     * validator=@Range()
     * @return array
     */
    private function resolveAnnotation($annotation, $clz)
    {
        $name = $annotation->getName();
        $class = $this->reflection->resolveImport($name, $clz);
        if ($class === IsA::CLASS) {
            return $this->resolveIsA($annotation, $clz);
        } else {
            return [$class, $annotation->getArguments()];
        }
    }

    /**
     * @return array
     */
    private function resolveIsA($annotation, $clz)
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
            return [IsArray::CLASS, ['element' => ['class' => $typeClass]]];
        } else {
            return [IsA::CLASS, ['class' => $typeClass]];
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
            $el = $refl->newInstanceArgs($args);
        } else {
            $el = new $clz($name, $args);
        }
        if (isset($args['label'])) {
            $el->setLabel($args['label']);
        }
        return $el;
    }
}
