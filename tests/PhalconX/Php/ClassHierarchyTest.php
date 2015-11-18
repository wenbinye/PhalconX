<?php
namespace PhalconX\Php;

use PhalconX\Test\TestCase;

/**
 * TestCase for ClassHierarchy
 */
class ClassHierarchyTest extends TestCase
{
    public function testAddClass()
    {
        $hierarchy = new ClassHierarchy;
        $hierarchy->addClass('A', 'B', ['IA']);
        $hierarchy->addClass('B', null, ['IB']);
        // print_r($hierarchy);

        $this->assertTrue($hierarchy->classExists('A'));
        $this->assertTrue($hierarchy->classExists('B'));
        $this->assertTrue($hierarchy->interfaceExists('IB'));
        $this->assertTrue($hierarchy->interfaceExists('IA'));
        
        $this->assertEquals('B', $hierarchy->getParent('A'));
        $this->assertEquals(['B'], $hierarchy->getAncestors('A'));
        $this->assertEquals([], $hierarchy->getAncestors('B'));

        $this->assertEquals(['IA', 'IB'], $hierarchy->getImplements('A'));
        $this->assertEquals(['A'], $hierarchy->getSubClasses('B'));

        $this->assertEquals(['A'], $hierarchy->getSubClasses('IA'));
        $this->assertEquals(['B', 'A'], $hierarchy->getSubClasses('IB'));
    }

    public function testDeclared()
    {
        $hierarchy = new ClassHierarchy;
        $hierarchy->addDeclared();
        $this->assertTrue($hierarchy->classExists('stdClass'));
    }
}
