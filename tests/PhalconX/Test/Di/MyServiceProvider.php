<?php
namespace PhalconX\Test\Di;

use PhalconX\Di\ServiceProvider;
use Phalcon\Config;
    
class MyServiceProvider extends ServiceProvider
{
    protected $services = [
        'finder' => Config::class
    ];
    
    public function provideFs()
    {
        return new Config(['fs' => true]);
    }
}
