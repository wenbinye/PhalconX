<?php
namespace PhalconX\Forms\Annotations;

use PhalconX\Test\TestCase;
use PhalconX\Test\Enum\Gender;
use PhalconX\Test\Helper;
use PhalconX\Validation\Validation;

/**
 * TestCase for Select
 */
class SelectTest extends TestCase
{
    private $form;

    /**
     * @before
     */
    public function setupForm()
    {
        $this->form = $this->get(Validation::class);
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
