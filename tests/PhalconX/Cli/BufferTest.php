<?php
namespace PhalconX\Cli;

use PhalconX\Test\TestCase;

/**
 * TestCase for Buffer
 */
class BufferTest extends TestCase
{
    function testAppend()
    {
        $buf = new Buffer;
        $buf->append('hello');
        $buf->append('world');
        $prefix = $buf->getAnyPrefix();
        $this->assertEquals($prefix, 'hello');
        $buf->removeBytesFromHead(strlen($prefix));
        $this->assertEquals($buf->getAsString(), 'world');
    }
}
