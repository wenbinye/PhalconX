<?php
namespace PhalconX\Test\Model;

use PhalconX\Mvc\Model;

class ScopeX extends Model
{
    public $name;

    public $description;
    
    public function getSource()
    {
        return 'scopes';
    }

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }
}
