<?php
namespace PhalconX\Cache;

use PhalconX\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

class ArrayPoolTest extends TestCase
{
    private $cache;

    public function setUp()
    {
        parent::setUp();
        $this->cache = new ArrayPool;
    }

    public function testGetItem()
    {
        $item = $this->cache->getItem('foo');
        $this->assertTrue($item instanceof CacheItemInterface);
        $this->assertFalse($item->isHit());
    }

    public function testSave()
    {
        $item = $this->cache->getItem('foo');
        $this->cache->save($item->set('bar'));

        $cached = $this->cache->getItem('foo');
        $this->assertTrue($cached->isHit());
        $this->assertEquals($cached->get(), 'bar');
    }
}