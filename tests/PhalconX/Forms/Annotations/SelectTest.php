<?php
namespace PhalconX\Forms\Annotations;

use PhalconX\Test\TestCase;
use PhalconX\Test\Enum\Gender;
use PhalconX\Test\Helper;
use PhalconX\Validation\Form;

/**
 * TestCase for Select
 */
class SelectTest extends TestCase
{
    private $form;

    public function setUp()
    {
        $this->form = new Form;
    }
    
    public function testRenderEnum()
    {
        $annotation = new Select([
            'name' => 'gender',
            'model' => 'Gender'
        ], Helper::createAnnotationContext($this, 'property', 'gender'));
        $elem = $annotation->getElement($this->form);
        $this->assertEquals('<select id="gender" name="gender">
	<option value="m">男</option>
	<option value="f">女</option>
</select>', $elem->render());
    }
}
