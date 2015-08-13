<?php
namespace PhalconX\Annotations\Validator;

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
use PhalconX\Exception;
use PhalconX\Enums\Enum;

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

    private static $TYPES = [
        'boolean' => Boolean::CLASS,
        'integer' => Integer::CLASS,
        'number' => Numericality::CLASS,
        'email' => Email::CLASS,
        'alpha' => Alpha::CLASS,
        'alnum' => Alnum::CLASS,
        'digit' => Digit::CLASS,
    ];
    
    public function getValidators()
    {
        $validators = [];
        if ($this->type) {
            if (isset(self::$TYPES[$this->type])) {
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
            if (is_array($this->enum)) {
                $domain = $this->enum;
            } else {
                $clz = $this->getDeclaringClass();
                if ($clz) {
                    $enumClass = $this->getAnnotations()->resolveImport($this->enum, $clz);
                } else {
                    $enumClass = $this->enum;
                }
                if (is_subclass_of($enumClass, Enum::CLASS)) {
                    $domain = array_map('strtolower', $enumClass::names());
                } else {
                    throw new Exception("Cannot infer enum domain from '{$this->enum}'");
                }
            }
            $validators[] = new InclusionIn(['domain' => $domain]);
        }
        if ($this->pattern) {
            $validators[] = new Regex(['pattern' => $this->pattern]);
        }
        return $validators;
    }
}
