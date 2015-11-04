<?php
namespace PhalconX\Helper;

use PhalconX\Test\TestCase;
use PhalconX\Test\Helper\Foo;

/**
 * TestCase for ClassHelper
 */
class ClassHelperTest extends TestCase
{
    public function testGetImports()
    {
        $imports = ClassHelper::getImports(Foo::class);
        // var_export($imports);
        $this->assertEquals(array (
            'TestCase' => 'PhalconX\\Test\\TestCase',
            'Helper' => 'PhalconX\\Helper\\ClassHelper',
            'PhpImportParser' => 'PhalconX\\Helper\\PhpImportParser',
            'Text' => 'Phalcon\\Text',
        ), $imports);
    }

    public function testGetNamespaceName()
    {
        $this->assertEquals(ClassHelper::getNamespaceName('Foo\\Bar'), 'Foo\\');
    }

    public function testGetShortName()
    {
        $this->assertEquals(ClassHelper::getShortName('Foo\\Bar'), 'Bar');
    }

    public function testGetClasses()
    {
        $refl = new \ReflectionClass(Foo::class);
        $classes = ClassHelper::getClasses($refl->getFilename());
        $this->assertEquals([Foo::class], $classes);
    }
}
