<?php
namespace PhalconX;

use PhalconX\Test\Dataset;
use PHPUnit_Framework_TestCase;

/**
 * Basic test case
 *
 * 1. provide di auto restore
 * 2. provide dataset access helper
 */
abstract class TestCase extends PHPUnit_Framework_TestCase
{
    use DataSet;

    protected function getFixturesDir()
    {
        return __DIR__ . '/fixtures';
    }
}
