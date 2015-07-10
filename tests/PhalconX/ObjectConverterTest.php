<?php
namespace PhalconX;

use PhalconX\Test\TestCase;
use PhalconX\Test\Models\Pet;
use PhalconX\Test\Models\Tag;
use PhalconX\Test\Models\Category;

class ObjectConverterTest extends TestCase
{
    private $converter;

    public function setUp()
    {
        Util::di()->setShared('reflection', 'PhalconX\Reflection');
        $this->converter = new ObjectConverter;
    }

    public function testConvertArray()
    {
        $result = $this->converter->convert(array(
            'id' => 1,
            'category' => array('id' => 1, 'name' => 'dog'),
            'tags' => array(
                array('id' => 1, 'name' => 'puppy')
            )
        ), Pet::CLASS);
        $this->validate($result);
    }

    public function testConvertJson()
    {
        $data = '{"id":1,"category":{"id":1,"name":"dog"},"tags":[{"id":1,"name":"puppy"}]}';
        $result = $this->converter->convert($data, Pet::CLASS, 'json');
        // print_r($result);
        $this->validate($result);
    }

    public function testConvertObject()
    {
        $data = json_decode('{"id":1,"category":{"id":1,"name":"dog"},"tags":[{"id":1,"name":"puppy"}]}');
        $result = $this->converter->convert($data, Pet::CLASS, 'object');
        // print_r($result);
        $this->validate($result);
    }

    private function validate($result)
    {
        $this->assertTrue($result instanceof Pet);
        $this->assertTrue($result->category instanceof Category);
        $this->assertTrue(is_array($result->tags));
        $this->assertTrue($result->tags[0] instanceof Tag);
    }
}
