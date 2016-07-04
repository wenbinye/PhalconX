<?php
namespace PhalconX\Annotation;

use PhalconX\Test\TestCase;
use PhalconX\Test\Annotation\Form;
use PhalconX\Test\Annotation\FormImported;
use PhalconX\Test\Annotation\FormImportAlias;
use PhalconX\Test\Annotation\Validators;

/**
 * TestCase for Annotations
 */
class AnnotationsTest extends TestCase
{
    private $annotations;

    /**
     * @before
     */
    public function setupAnnotations()
    {
        $this->annotations = $this->get(Annotations::class);
    }

    public function testGetNotImported()
    {
        $anno = $this->annotations->get(Form::class);
        $this->assertTrue(empty($anno));
    }

    public function testGetImported()
    {
        $anno = $this->annotations->get(FormImported::class);
        $this->assertAnnotationsMatches($anno);
    }

    public function testImport()
    {
        $this->annotations->import([
            Validators\Max::class,
            Validators\Range::class,
            Validators\Match::class,
        ]);
        $anno = $this->annotations->get(Form::class);
        $this->assertAnnotationsMatches($anno);
    }

    public function testImportAlias()
    {
        $this->annotations->import([
            Validators\Max::class,
            Validators\Range::class => 'Between',
            Validators\Match::class,
        ]);
        $anno = $this->annotations->get(FormImportAlias::class);
        // var_export($anno);
        $this->assertAnnotationsMatches($anno);
    }
    
    private function assertAnnotationsMatches($annotations)
    {
        $this->assertEquals(count($annotations), 3);
        $anno = $annotations[0];
        $this->assertTrue($anno instanceof Validators\Max);
        $this->assertEquals($anno->max, 10);
        $this->assertTrue($anno->isOnProperty());
    }
}
