<?php
namespace PhalconX\DI;

use Phalcon\DI;

trait Injectable
{
    private $di;

    public function getDI()
    {
        if (!$this->di) {
            $this->di = DI::getDefault();
        }
        return $this->di;
    }

    public function setDI(\Phalcon\DiInterface $di)
    {
        $this->di = $di;
    }

    public function __get($property)
    {
        return $this->getDI()->get($property);
    }
}
