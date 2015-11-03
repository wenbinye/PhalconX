<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\Regex as RegexValidator;

/**
 * TestCase for Boolean
 */
class RegexTest extends TestCase
{
    protected static $annotationClass = Regex::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation(['pattern' => '/foo/']);
        $this->assertTrue($annotation->getValidator($this->form) instanceof RegexValidator);
    }

    /**
     * @dataProvider values
     */
    public function testDefaultProperty($value, $expect)
    {
        $validator = $this->getAnnotation(['/^foo/'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value, 'foo' => 'foo']);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    /**
     * @dataProvider values
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation(['pattern' => '/^foo/'])->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value, 'foo' => 'foo']);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function values()
    {
        return [
            ['foo', 0],
            ["bar", 1],
        ];
    }
}
