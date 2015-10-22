<?php
namespace PhalconX\Annotations\Validator;

use Phalcon\Text;
use Phalcon\Annotations\Annotation as PhalconAnnotation;
use Phalcon\Validation\Validator\InclusionIn;
use Phalcon\Validation\Validator\Numericality;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Alpha;
use Phalcon\Validation\Validator\Alnum;
use Phalcon\Validation\Validator\Digit;
use Phalcon\Validation\Validator\Regex;
use PhalconX\Validators\Range;
use PhalconX\Validators\StringLength;
use PhalconX\Validators\Boolean;
use PhalconX\Validators\Integer;
use PhalconX\Validators\IsArray;
use PhalconX\Validators\Multiple;
use PhalconX\Validators\Datetime;
use PhalconX\Exception;
use PhalconX\Enums\Enum;
use Phalcon\Mvc\Model;

class Valid extends Validator
{
    public $type;

    public $pattern;

    public $element;

    public $minimum;

    public $maximum;

    public $maxLength;

    public $minLength;

    public $enum;

    public $criteria;
    
    public $using;

    private static $TYPES = [
        'boolean' => Boolean::CLASS,
        'integer' => Integer::CLASS,
        'number'  => Numericality::CLASS,
        'email'   => Email::CLASS,
        'alpha'   => Alpha::CLASS,
        'alnum'   => Alnum::CLASS,
        'digit'   => Digit::CLASS,
    ];
    
    public function getValidators()
    {
        $validators = [];
        if ($this->type) {
            if ($this->type instanceof PhalconAnnotation) {
                $validators[] = $this->createValidator();
            } elseif ($this->type == 'datetime') {
                $validators[] = new Datetime(['pattern' => $this->pattern]);
            } elseif (isset(self::$TYPES[$this->type])) {
                $clz = self::$TYPES[$this->type];
                $validators[] = new $clz;
            } elseif ($this->type == 'array') {
                $args = [];
                if (isset($this->element)) {
                    if (is_array($this->element)) {
                        $element = new self($this->element);
                        $element->setContext($this->getContext());
                    } elseif ($this->element instanceof PhalconAnnotation) {
                        $element = $this->resolve($this->element);
                    } elseif (is_string($this->element)) {
                        $element = new self(['type' => $this->element]);
                    } else {
                        throw new Exception("Invalid array element '{$this->element}'");
                    }
                    $element->setAnnotations($this->getAnnotations());
                    $args['element'] = new Multiple(['validators' => $element->process()]);
                }
                $validators[] = new IsArray($args);
            } elseif (ctype_upper($this->type[0])) {
                $validators[] = $this->createValidator();
            }
        }
        if (isset($this->maximum) || isset($this->minimum)) {
            $validators[] = new Range([
                'minimum' => $this->minimum,
                'maximum' => $this->maximum
            ]);
        }
        if (isset($this->maxLength) || isset($this->minLength)) {
            $validators[] = new StringLength([
                'max' => $this->maxLength,
                'min' => $this->minLength
            ]);
        }
        if ($this->enum) {
            $validators[] = new InclusionIn(['domain' => $this->getEnumDomain()]);
        }
        if ($this->pattern && $this->type != 'datetime') {
            $validators[] = new Regex(['pattern' => $this->pattern]);
        }
        return $validators;
    }

    private function getEnumDomain()
    {
        if (is_array($this->enum)) {
            return $this->enum;
        } else {
            $enumClass = $this->enum;
            if (Text::endsWith($enumClass, '.values')) {
                $enumClass = substr($enumClass, 0, -7);
                $useEnumValue = true;
            }
            $clz = $this->getDeclaringClass();
            if ($clz) {
                $enumClass = $this->resolveImport($enumClass, $clz);
            }
            if (is_subclass_of($enumClass, Enum::CLASS)) {
                if (isset($useEnumValue)) {
                    return $enumClass::values();
                } else {
                    return array_map('strtolower', $enumClass::names());
                }
            } elseif (is_subclass_of($enumClass, Model::CLASS)) {
                if (empty($this->using)) {
                    throw new Exception("The 'using' parameter is required for " . $this);
                }
                $col = $this->using;
                $domain = [];
                $this->criteria['columns'] = [$col];
                foreach ($enumClass::find($this->criteria) as $row) {
                    $domain[] = $row[$col];
                }
                return $domain;
            } else {
                throw new Exception("Cannot infer enum domain from '{$this->enum}'");
            }
        }
    }

    private function createValidator()
    {
        if ($this->type instanceof PhalconAnnotation) {
            $validator = $this->type->getName();
            $args = $this->type->getArguments();
        } else {
            $validator = $this->type;
        }
        $clz = $this->getDeclaringClass();
        if ($clz) {
            $validator = $this->resolveImport($validator, $clz);
        }
        if (isset($args)) {
            return new $validator($args);
        } else {
            return new $validator();
        }
    }
}
