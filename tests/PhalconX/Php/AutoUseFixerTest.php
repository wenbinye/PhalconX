<?php
namespace PhalconX\Php;

use PhalconX\Test\TestCase;

/**
 * TestCase for AutoUse
 */
class AutoUseFixerTest extends TestCase
{
    private $hierarchy;

    /**
     * @before
     */
    public function beforeClass()
    {
        $hierarchy = new ClassHierarchy();
        $hierarchy->addDeclared();
        $hierarchy->addClass('PhalconX\Forms\Annotations\Text', 'PhalconX\Annotation\Annotation');
        $this->hierarchy = $hierarchy;
    }
    
    /**
     * @dataProvider files
     */
    public function testAutouse($file)
    {
        $autouse = new AutoUseFixer($this->getDatasetFile("auto-use/$file.php"), $this->hierarchy, $this->logger);
        $code = $autouse->fix();
        // echo $code;
        $this->assertEquals($this->dataset("auto-use/$file-fixed.php", 'txt'), $code);
    }

    public function files()
    {
        return [
            ['no-use'],
            ['one-use'],
            ['no-namespace'],
            ['multiple-use'],
            ['annotation']
        ];
    }
}
