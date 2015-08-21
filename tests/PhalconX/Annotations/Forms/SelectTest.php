<?php
namespace PhalconX\Annotations\Forms;

use PhalconX\Test\TestCase;
use PhalconX\Enums\Boolean;
use Phalcon\Mvc\Model;

/**
 * TestCase for Select
 */
class SelectTest extends TestCase
{
    /**
     * @Select(enum=Boolean, name=select)
     */
    public function testEnum()
    {
        $annotations = $this->annotations->get(__CLASS__)
            ->method('testEnum')
            ->isa(Select::CLASS);
        $elem = $annotations[0]->process();
        $this->assertEquals($elem->render(),
'<select id="select" name="select">
	<option value="1">Yes</option>
	<option value="0">No</option>
</select>');
    }

    /**
     * @Select(options=['否', '是'], name=select)
     */
    public function testOptions()
    {
        $annotations = $this->annotations->get(__CLASS__)
            ->method('testOptions')
            ->isa(Select::CLASS);
        $elem = $annotations[0]->process();
        $this->assertEquals($elem->render(),
'<select id="select" name="select">
	<option value="0">否</option>
	<option value="1">是</option>
</select>');
    }

    /**
     * @Select(enum=Scope, using=[name, description], name=select)
     */
    public function testModel()
    {
        $annotations = $this->annotations->get(__CLASS__)
            ->method('testModel')
            ->isa(Select::CLASS);
        $elem = $annotations[0]->process();
        // print_r($elem);
        $this->assertEquals($elem->render(),
'<select id="select" name="select">
	<option value="email">Email Address</option>
	<option value="basic">Basic operations</option>
</select>');
    }
}

class Scope extends Model
{
    public $name;
    public $description;

    public function getSource()
    {
        return 'scopes';
    }
}
