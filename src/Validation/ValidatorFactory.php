<?php
namespace PhalconX\Validation;

use Phalcon\Cache\BackendInterface as Cache;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\PresenceOf;
use PhalconX\Annotation\Context;
use PhalconX\Helper\ClassResolver;
use PhalconX\Helper\ArrayHelper;
use PhalconX\Validation\Annotations\Boolean;
use PhalconX\Validation\Annotations\Integer;
use PhalconX\Validation\Annotations\Number;
use PhalconX\Validation\Annotations\Email;
use PhalconX\Validation\Annotations\Url;
use PhalconX\Validation\Annotations\IsArray;
use PhalconX\Validation\Annotations\IsA;
use PhalconX\Validation\Annotations\Enum;
use PhalconX\Validation\Validators\Datetime;
use PhalconX\Validation\Validators\Range;

class ValidatorFactory
{
    private static $TYPES = [
        'boolean' => Boolean::CLASS,
        'integer' => Integer::CLASS,
        'number'  => Number::CLASS,
        'email'   => Email::CLASS,
        'url' => Url::CLASS
    ];

    private $form;
    private $classResolver;
    
    public function __construct(Form $form)
    {
        $this->form = $form;
        $this->classreSolver = new ClassResolver($this->form->getCache());
    }
    
    /**
     * required
     * type
     * pattern
     * element
     * min
     * max
     * exclusiveMinimum
     * exclusiveMaximum
     * maxLength
     * minLength
     * enum
     * attribute
     */
    public function create($options, Context $context = null)
    {
        $validators = [];
        if (!empty($options['required'])) {
            $validators[] = new PresenceOf();
        }
        $type = ArrayHelper::fetch($options, 'type');
        if (isset($type)) {
            if ($type == 'datetime') {
                $validators[] = new Datetime([
                    'pattern' => ArrayHelper::fetch($options, 'pattern')
                ]);
            } elseif (isset(self::$TYPES[$type])) {
                $validators[] = (new self::$TYPES[$type]([]))->getValidator($this->form);
            } elseif ($type == 'array') {
                $validators[] = (new IsArray([
                    'element' => ArrayHelper::fetch($options, 'element')
                ], $context))->getValidator($this->form);
            } else {
                $validators[] = (new IsA([
                    'class' => $type
                ], $context))->getValidator($this->form);
            }
        }
        if (isset($options['min']) || isset($options['max'])) {
            $validators[] = new Range([
                'min' => ArrayHelper::fetch($options, 'min'),
                'max' => ArrayHelper::fetch($options, 'max'),
                'exclusiveMinimum' => ArrayHelper::fetch($options, 'exclusiveMinimum'),
                'exclusiveMaximum' => ArrayHelper::fetch($options, 'exclusiveMaximum')
            ]);
        }
        if (isset($options['minLength']) || isset($options['maxLength'])) {
            $validators[] = new StringLength([
                'min' => ArrayHelper::fetch($options, 'minLength'),
                'max' => ArrayHelper::fetch($options, 'maxLength')
            ]);
        }
        if (isset($options['enum'])) {
            $validators[] = (new Enum([
                'model' => $options['enum'],
                'attribute' => ArrayHelper::fetch($options, 'attribute'),
            ], $context))->getValidator($this->form);
        }
        if (isset($options['pattern']) && $type != 'datetime') {
            $validators[] = new Regex([ 'pattern' => $options['pattern'] ]);
        }
        return $validators;
    }
}
