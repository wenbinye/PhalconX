<?php
namespace PhalconX\Test;

use Phalcon\Di\InjectionAwareInterface;
use PhalconX\Di\Injectable;

abstract class TestCase extends \PHPUnit_Framework_TestCase implements InjectionAwareInterface
{
    use Injectable;
    use Dataset;
    use Accessible;
}
