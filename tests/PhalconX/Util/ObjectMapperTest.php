<?php
namespace PhalconX\Util;

use Phalcon\Di;
use PhalconX\Test\TestCase;
use PhalconX\Test\Models\Pet;
use PhalconX\Test\Models\Tag;
use PhalconX\Test\Models\Category;

class ObjectMapperTest extends TestCase
{
    private $objectMapper;

    public function setUp()
    {
        Di::getDefault()->setShared('reflection', 'PhalconX\Util\Reflection');
        $this->objectMapper = new ObjectMapper;
    }

    public function testMapArray()
    {
        $result = $this->objectMapper->map(array(
            'id' => 1,
            'category' => array('id' => 1, 'name' => 'dog'),
            'tags' => array(
                array('id' => 1, 'name' => 'puppy')
            )
        ), Pet::CLASS);
        $this->validate($result);
    }

    public function testMapJson()
    {
        $data = '{"id":1,"category":{"id":1,"name":"dog"},"tags":[{"id":1,"name":"puppy"}]}';
        $result = $this->objectMapper->map($data, Pet::CLASS, 'json');
        // print_r($result);
        $this->validate($result);
    }

    public function testMalformedJson()
    {
        try {
            $result = $this->objectMapper->map('', Pet::CLASS, 'json');
            $this->fail();
        } catch (\InvalidArgumentException $e) {
        }
    }
    
    public function testMapObject()
    {
        $data = json_decode('{"id":1,"category":{"id":1,"name":"dog"},"tags":[{"id":1,"name":"puppy"}]}');
        $result = $this->objectMapper->map($data, Pet::CLASS, 'object');
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
