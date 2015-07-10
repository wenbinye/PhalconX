<?php
namespace PhalconX\Di;

use Phalcon\Di;

trait Injectable
{
    private $di;

    public function getDi()
    {
        if (!$this->di) {
            $this->di = Di::getDefault();
        }
        return $this->di;
    }

    public function setDi(\Phalcon\DiInterface $di)
    {
        $this->di = $di;
    }

    public function __get($property)
    {
        return $this->getDi()->get($property);
    }
}
