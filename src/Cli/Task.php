<?php
namespace PhalconX\Cli;

use PhalconX\Di\Injectable;
use PhalconX\Mvc\SimpleModel;

abstract class Task extends SimpleModel implements TaskInterface
{
    use Injectable;
}
