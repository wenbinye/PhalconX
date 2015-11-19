<?php
namespace PhalconX\Serializer;

use PhalconX\Test\TestCase;
use PhalconX\Annotation\Annotations;
use PhalconX\Test\Serializer\Organization;
use PhalconX\Test\Serializer\Member;
use PhalconX\Test\Serializer\Company;

/**
 * TestCase for Serializer
 */
class SerializerTest extends TestCase
{
    private $serializer;

    /**
     * @before
     */
    public function setupSerializer()
    {
        $this->serializer = new Serializer();
    }
    
    public function testDeserializeType()
    {
        $json = "{\"name\": \"Les-Tilleuls.coop\",\"members\":[{\"name\":\"K\\u00e9vin\"}]}";
        $org = $this->serializer->deserialize($json, Organization::class);
        // print_r($org);

        $this->assertTrue($org instanceof Organization);
        $this->assertEquals($org->getName(), "Les-Tilleuls.coop");
        $members = $org->getMembers();
        $this->assertTrue(is_array($members));
        $this->assertTrue($members[0] instanceof Member);
        $this->assertEquals($members[0]->getName(), "Kévin");
    }

    public function testDeserializeName()
    {
        $json = '{"org_name": "Acme Inc.", "org_address": "123 Main Street, Big City"}';
        $obj = $this->serializer->deserialize($json, Company::class);
        // print_r($obj);
        $this->assertEquals($obj->name, 'Acme Inc.');
    }

    public function testSerializeName()
    {
        $obj = new Company;
        $obj->name = 'Acme Inc.';
        $obj->address = '123 Main Street, Big City';
        $this->assertEquals($this->serializer->serialize($obj), '{"org_name":"Acme Inc.","org_address":"123 Main Street, Big City"}');
    }

    public function testSerializeType()
    {
        $org = new Organization;
        $org->setName('Les-Tilleuls.coop');
        $member = new Member;
        $member->setName('Kevin');
        $org->setMembers([$member]);
        
        $this->assertEquals($this->serializer->serialize($org),
                            '{"name":"Les-Tilleuls.coop","members":[{"name":"Kevin"}]}');
    }
}
