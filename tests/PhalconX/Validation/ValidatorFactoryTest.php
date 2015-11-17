<?php
namespace PhalconX\Validation;

use PhalconX\Test\TestCase;
use PhalconX\Annotation\Context;
use PhalconX\Test\Enum\Gender;
use PhalconX\Test\Model\Scope;
use PhalconX\Test\Helper;

use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\Url;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Numericality;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\InclusionIn;
use PhalconX\Helper\ClassResolver;
use PhalconX\Helper\ArrayHelper;
use PhalconX\Validation\Validators\Boolean;
use PhalconX\Validation\Validators\IsArray;
use PhalconX\Validation\Validators\IsA;
use PhalconX\Validation\Validators\Enum;
use PhalconX\Validation\Validators\Datetime;
use PhalconX\Validation\Validators\Range;
use PhalconX\Validation\Validators\InclusionInModel;

/**
 * TestCase for ValidatorFactory
 */
class ValidatorFactoryTest extends TestCase
{
    private $factory;

    /**
     * @before
     */
    public function setupFactory()
    {
        $this->factory = new ValidatorFactory(new Validation());
    }

    private function create($options)
    {
        return $this->factory->create($options, Helper::createAnnotationContext($this, 'property', 'value'));
    }

    /**
     * @dataProvider validators
     */
    public function testCreate($spec, $expected)
    {
        $validators = $this->create($spec);
        $this->assertValidators($validators, $expected);
    }

    public function validators()
    {
        return [
            [['required'=> true], [
                [PresenceOf::class]
            ]],
            [['type' => 'datetime'], [
                [Datetime::class]
            ]],
            [['type' => 'datetime', 'pattern' => 'Y-m-d'], [
                [Datetime::class, ['pattern' => 'Y-m-d']]
            ]],
            [['type' => 'boolean'], [
                [Boolean::class]
            ]],
            [['type' => 'integer'], [
                [Regex::class]
            ]],
            [['type' => 'number'], [
                [Numericality::class]
            ]],
            [['type' => 'email'], [
                [Email::class]
            ]],
            [['type' => 'url'], [
                [Url::class]
            ]],
            [['min' => 10], [
                [Range::class, ['min' => 10]]
            ]],
            [['maxLength' => 10], [
                [StringLength::class, ['max' => 10]]
            ]],
            [['enum' => ['a', 'b']], [
                [InclusionIn::class, ['domain' => ['a', 'b']]]
            ]],
            [['enum' => 'Gender'], [
                [InclusionIn::class, ['domain' => ['male', 'female']]]
            ]],
            [['enum' => 'Gender.values'], [
                [InclusionIn::class, ['domain' => ['m', 'f']]]
            ]],
            [['enum' => 'Scope', 'attribute' => 'name'], [
                [InclusionInModel::class, [
                    'model' => Scope::class,
                    'attribute' => 'name'
                ]]
            ]]
        ];
    }
    
    private function assertValidators($validators, $expected)
    {
        $this->assertEquals(count($validators), count($expected));
        foreach ($validators as $i => $validator) {
            $theValidator = $expected[$i];
            $this->assertEquals(get_class($validator), $theValidator[0]);
            if (isset($theValidator[1])) {
                foreach ($theValidator[1] as $name => $val) {
                    $this->assertEquals($validator->getOption($name), $val);
                }
            }
        }
    }
}
