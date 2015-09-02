<?php
namespace PhalconX\Test;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    use DiService;
    use Dataset;
    use Accessible;

    public function tearDown()
    {
        $this->restoreServices();
    }
}
