<?php
namespace PhalconX\Annotations;

use PhalconX\Test\TestCase;
use PhalconX\Annotations\Mvc\Router\Get;

/**
 * TestCase for Collection
 */
class CollectionTest extends TestCase
{
    public function testFilter()
    {
        $annotations = $this->annotations->scan(
            $this->config->testBaseDir . '/PhalconX/Test/Controllers'
        );
        $annotations = new Collection($annotations);
        /* 
         * $r = $annotations->methodsOnly()
         *     ->isa(Get::CLASS)
         *     ->method('indexAction');
         */
        $r = $annotations->filter([
            'methodsOnly' => true,
            'isa' => Get::CLASS,
            'method' => 'indexAction'
        ]);
        print_r($r);
    }
}
