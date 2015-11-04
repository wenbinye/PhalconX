<?php
namespace PhalconX\Test;

/**
 * Basic test case
 *
 * 1. provide di auto restore
 * 2. provide dataset access helper
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    use DiService;
    use Dataset;
}
