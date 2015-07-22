<?php
namespace PhalconX;

use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\InclusionIn;
use PhalconX\Validators\Range;
use PhalconX\Validators\StringLength;
use PhalconX\Mvc\SimpleModel;

class ValidatorSpec extends SimpleModel
{
    public $name;

    public $value;

    public $type;

    public $default;

    public $required;

    public $validator;

    public $element;

    public $minimum;

    public $maximum;

    public $maxLength;

    public $minLength;

    public $enum;

    private $validatorFactory;

    public function __construct($data, $validatorFactory)
    {
        parent::__construct($data);
        if (empty($this->name)) {
            throw new \UnexpectedValueException("Validator should contain a name");
        }
        $this->validatorFactory = $validatorFactory;
    }

    public function hasValue()
    {
        return isset($this->value) && $this->value !== '';
    }

    private function createValidator($validator)
    {
        return $this->validatorFactory->createValidator($validator);
    }
    
    public function toValidators()
    {
        $validators = [];
        if ($this->required) {
            $validators[] = new PresenceOf();
        }
        if ($this->validator) {
            $validators[] = $this->createValidator($this->validator);
        }
        if ($this->type) {
            $validator = $this->createValidator((array) $this);
            if (isset($validator)) {
                $validators[] = $validator;
            }
            // 处理 string, integer, number 类型 min, max 属性
            if (in_array($this->type, ['integer', 'number'])) {
                if (isset($this->maximum) || isset($this->minimum)) {
                    $validators[] = new Range([
                        'minimum' => $this->minimum,
                        'maximum' => $this->maximum
                    ]);
                }
            } elseif ($this->type == 'string') {
                if (isset($this->maxLength) || isset($this->minLength)) {
                    $validators[] = new StringLength([
                        'max' => $this->maxLength,
                        'min' => $this->minLength
                    ]);
                } elseif ($this->enum && is_array($this->enum)) {
                    $validators[] = new InclusionIn([
                        'domain' => $this->enum
                    ]);
                }
            }
        }
        return $validators;
    }
}
