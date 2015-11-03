<?php
namespace PhalconX\Test\Model;

use Phalcon\Mvc\Model;

class Scope extends Model
{
    public $name;

    public $description;
    
    public function getSource()
    {
        return 'scopes';
    }
}
