<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\InclusionIn;
use PhalconX\Validation\Validators\InclusionInModel;
use PhalconX\Test\Enum\Gender;
use PhalconX\Test\Model\Bootstrap;
use PhalconX\Test\Model\Scope;

/**
 * TestCase for Url
 */
class EnumModelTest extends TestCase
{
    protected static $annotationClass = Enum::class;

    public function setUp()
    {
        parent::setUp();
        Bootstrap::setUp();
    }

    public function tearDown()
    {
        Bootstrap::tearDown();
    }

    /**
     * @dataProvider enums
     */
    public function testEnums($value, $expect)
    {
        $validator = $this->getAnnotation([Scope::class, 'attribute' => 'name'])
            ->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }
    
    public function enums()
    {
        return [
            ['email', 0],
            ["admin", 1]
        ];
    }
}
