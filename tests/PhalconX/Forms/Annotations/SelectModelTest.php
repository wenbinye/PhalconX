<?php
namespace PhalconX\Forms\Annotations;

use PhalconX\Test\TestCase;
use PhalconX\Test\Helper;
use PhalconX\Validation\Form;
use PhalconX\Test\Model\Scope;

/**
 * TestCase for Select
 */
class SelectModelTest extends TestCase
{
    private $form;

    /**
     * @before
     */
    public function setupTable()
    {
        $this->form = new Form;
        $this->db->execute($this->dataset('tables/scope.sql'));
    }
    
    public function testRenderModel()
    {
        $annotation = new Select([
            'name' => 'scope',
            'model' => 'Scope',
            'using' => ['name', 'description']
        ], Helper::createAnnotationContext($this, 'property', 'scope'));
        $elem = $annotation->getElement($this->form);
        $this->assertEquals('<select id="scope" name="scope">
	<option value="email">Email Address</option>
	<option value="basic">Basic operations</option>
</select>', $elem->render());
    }
}
