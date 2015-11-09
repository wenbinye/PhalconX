<?php
namespace PhalconX\Test\Serializer;

use PhalconX\Validation\Annotations\IsArray;

class Organization
{
    private $name;
    private $members;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @IsArray(Member)
     */
    public function setMembers(array $members)
    {
        $this->members = $members;
    }

    public function getMembers()
    {
        return $this->members;
    }
}
