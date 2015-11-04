<?php
namespace PhalconX\Mvc;

use PhalconX\Test\TestCase;
use PhalconX\Test\Model\ScopeX as Scope;

/**
 * TestCase for Model
 */
class ModelTest extends TestCase
{
    /**
     * @before
     */
    public function setupModel()
    {
        $this->db->execute($this->dataset('tables/scope.sql'));
    }
    
    public function testIsChangedNew()
    {
        $model = new Scope;
        $this->assertTrue($model->isChanged());
    }
    
    public function testIsChangedNotChange()
    {
        $model = Scope::findFirst();
        // print_r($model);
        $this->assertFalse($model->isChanged());
    }
    
    public function testIsChangedChanged()
    {
        $model = Scope::findFirst();
        $model->description = 'hello';
        $this->assertTrue($model->isChanged());
    }
}
