<?php
namespace PhalconX\Di;

use Phalcon\DiInterface as PhalconDiInterface;

interface DiInterface extends PhalconDiInterface
{
    /**
     * Events hook for request start
     */
    public function startRequest();

    /**
     * Events hoot for request end
     */
    public function endRequest();
}
