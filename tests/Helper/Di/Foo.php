<?php
namespace PhalconX\Helper\Di;

use Psr\Log\LoggerInterface;

class Foo
{
    public $logger;

    public $name;

    public $names;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function addName($name)
    {
        $this->names[] = $name;
    }
}