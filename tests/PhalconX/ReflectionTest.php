<?php
namespace PhalconX;

use PhalconX\Test\TestCase;

class ReflectionTest extends TestCase
{
    private $reflection;

    public function setUp()
    {
        $this->reflection = new Reflection();
    }
    
    public function testResolveImport()
    {
        foreach ([['IsArray', 'PhalconX\Validators\IsArray'],
                  ['Validation\Message', 'Phalcon\Validation\Message'],
                  ['ValidationException', 'PhalconX\ValidationException'],
        ] as $case) {
            list($name, $clz) = $case;
            $ret = $this->reflection->resolveImport($name, \PhalconX\Validator::CLASS);
            $this->assertEquals($ret, $clz);
        }
    }
}
