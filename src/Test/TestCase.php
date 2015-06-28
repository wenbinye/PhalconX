<?php
namespace PhalconX\Test;

use Phalcon\DI\InjectionAwareInterface;
use PhalconX\DI\Injectable;

use Symfony\Component\Yaml\Yaml;

abstract class TestCase extends \PHPUnit_Framework_TestCase implements InjectionAwareInterface
{
    use Injectable;
    use Dataset;
    use Accessible;
}
