<?php
namespace PhalconX\Validation\Annotations;

use PhalconX\Test\Validation\Annotations\TestCase;
use Phalcon\Validation\Validator\Url as UrlValidator;

/**
 * TestCase for Url
 */
class UrlTest extends TestCase
{
    protected static $annotationClass = Url::class;

    public function testValidator()
    {
        $annotation = $this->getAnnotation();
        $this->assertTrue($annotation->getValidator($this->form) instanceof UrlValidator);
    }

    /**
     * @dataProvider urls
     */
    public function testValidate($value, $expect)
    {
        $validator = $this->getAnnotation()->getValidator($this->form);
        $this->validation->add('value', $validator);
        $errors = $this->validation->validate(['value' => $value]);
        $this->assertEquals(count($errors), $expect, "value = $value");
    }

    public function urls()
    {
        return [
            ["file:///home", 0],
            ["http://user@host.com:8888/path", 0]
        ];
    }
}
