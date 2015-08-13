<?php
namespace PhalconX;

use PhalconX\Test\TestCase;
use PhalconX\Annotations\Validator\Validator;
use PhalconX\Annotations\ContextType;

/**
 * TestCase for Memory
 */
class AnnotationsTest extends TestCase
{
    private $annotations;

    public function setUp()
    {
        $this->annotations = new Annotations();
    }

    public function testScan()
    {
        $annotations = $this->annotations->scan(
            $this->config->testBaseDir.'/PhalconX/Test/Models', Validator::CLASS, ContextType::T_PROPERTY
        );
        // print_r($annotations);
        $this->assertTrue(!empty($annotations));
    }
}
