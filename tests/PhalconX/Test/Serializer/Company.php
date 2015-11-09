<?php
namespace PhalconX\Test\Serializer;

use PhalconX\Serializer\Annotations\SerializeName;

class Company
{
    /**
     * @SerializeName("org_name")
     */
    public $name;

    /**
     * @SerializeName("org_address")
     */
    public $address;
}
