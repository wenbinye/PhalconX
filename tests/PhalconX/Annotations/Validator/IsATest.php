<?php
namespace PhalconX\Annotations\Validator;

use PhalconX\Test\TestCase;
use PhalconX\Annotations\Context;

/**
 * TestCase for IsA
 */
class IsATest extends TestCase
{
    public function testIsArray()
    {
        $isa = new IsA(['\stdClass[]']);
        $isa->setAnnotations($this->annotations);
        $isa->setContext(new Context(['declaringClass' => __CLASS__]));
        $this->assertTrue($isa->isArray());
        $this->assertEquals($isa->getType(), '\stdClass');
    }
}
