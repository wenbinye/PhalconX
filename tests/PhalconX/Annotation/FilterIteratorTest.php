<?php
namespace PhalconX\Annotation;

use PhalconX\Test\TestCase;

/**
 * TestCase for Annotations
 */
class FilterIteratorTest extends TestCase
{
    private $annotations;
    
    public function setUp()
    {
        $annotations = new Annotations;
        $this->annotations = $annotations->filter($annotations->get(FormFilter::class));
    }

    public function testOnClass()
    {
        $anno = array_values(iterator_to_array($this->annotations->onClass()));
        $this->assertEquals(count($anno), 1);
        $this->assertTrue($anno[0]->isOnClass());
    }

    public function testOnMethods()
    {
        $anno = array_values(iterator_to_array($this->annotations->onMethods()));
        $this->assertEquals(count($anno), 1);
        $this->assertTrue($anno[0]->isOnMethod());
    }

    public function testOnClassOrMethods()
    {
        $anno = array_values(iterator_to_array($this->annotations->onClassOrMethods()));
        $this->assertEquals(count($anno), 2);
    }

    public function testOnProperties()
    {
        $anno = array_values(iterator_to_array($this->annotations->onProperties()));
        $this->assertEquals(count($anno), 3);
    }

    public function testOnClassOrProperties()
    {
        $anno = array_values(iterator_to_array($this->annotations->onClassOrProperties()));
        $this->assertEquals(count($anno), 4);
    }

    public function testIs()
    {
        $anno = array_values(iterator_to_array($this->annotations->is(Validators\Max::class)));
        $this->assertEquals(count($anno), 2);
        $this->assertTrue($anno[0] instanceof Validators\Max);
    }

    public function testOnMethod()
    {
        $anno = array_values(iterator_to_array($this->annotations->onMethod('foo')));
        $this->assertEquals(count($anno), 1);
        $this->assertEquals($anno[0]->getContext()->getName(), 'foo');
    }

    public function testOnProperty()
    {
        $anno = array_values(iterator_to_array($this->annotations->onProperty('size')));
        $this->assertEquals(count($anno), 1);
        $this->assertEquals($anno[0]->getContext()->getName(), 'size');
    }

    public function testCombo()
    {
        $anno = array_values(iterator_to_array($this->annotations->is(Validators\Max::class)
                                               ->onProperty('size')));
        
        $this->assertEquals(count($anno), 1);
        $this->assertEquals($anno[0]->getContext()->getName(), 'size');
    }
}
