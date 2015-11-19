<?php
namespace PhalconX\Test\Queue;

use PhalconX\Queue\Job;
use PhalconX\Di\Injectable;

class MyJob extends Job
{
    use Injectable;
    
    public $query;

    private $appService;

    public function process()
    {
    }
}