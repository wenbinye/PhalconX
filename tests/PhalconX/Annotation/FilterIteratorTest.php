<?php
namespace PhalconX\Annotation;

use PhalconX\Test\TestCase;
use PhalconX\Test\Annotation\FormFilter;
use PhalconX\Test\Annotation\Validators;

/**
 * TestCase for Annotations
 */
class FilterIteratorTest extends TestCase
{
    private $annotations;

    /**
     * @before
     */
    public function setupAnnotations()
    {
        $annotations = new Annotations;
        $this->annotations = $annotations->filter($annotations->get(FormFilter::class));
    }

    public function testOnClass()
    {
        $anno = $this->annotations->onClass()->toArray();
        $this->assertEquals(count($anno), 1);
        $this->assertTrue($anno[0]->isOnClass());
    }

    public function testOnMethods()
    {
        $anno = $this->annotations->onMethods()->toArray();
        $this->assertEquals(count($anno), 1);
        $this->assertTrue($anno[0]->isOnMethod());
    }

    public function testOnClassOrMethods()
    {
        $anno = $this->annotations->onClassOrMethods()->toArray();
        $this->assertEquals(count($anno), 2);
    }

    public function testOnProperties()
    {
        $anno = $this->annotations->onProperties()->toArray();
        $this->assertEquals(count($anno), 3);
    }

    public function testOnClassOrProperties()
    {
        $anno = $this->annotations->onClassOrProperties()->toArray();
        $this->assertEquals(count($anno), 4);
    }

    public function testIs()
    {
        $anno = $this->annotations->is(Validators\Max::class)->toArray();
        $this->assertEquals(count($anno), 2);
        $this->assertTrue($anno[0] instanceof Validators\Max);
    }

    public function testOnMethod()
    {
        $anno = $this->annotations->onMethod('foo')->toArray();
        $this->assertEquals(count($anno), 1);
        $this->assertEquals($anno[0]->getContext()->getName(), 'foo');
    }

    public function testOnProperty()
    {
        $anno = $this->annotations->onProperty('size')->toArray();
        $this->assertEquals(count($anno), 1);
        $this->assertEquals($anno[0]->getContext()->getName(), 'size');
    }

    public function testCombo()
    {
        $anno = $this->annotations->is(Validators\Max::class)
            ->onProperty('size')
            ->toArray();
        
        $this->assertEquals(count($anno), 1);
        $this->assertEquals($anno[0]->getContext()->getName(), 'size');
    }
}
