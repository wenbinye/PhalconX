<?php
namespace PhalconX\Test;

use Phalcon\Di;

trait DiService
{
    private static $DI = [];

    private $di;

    public function getDi()
    {
        return $this->di;
    }

    public function setDi($di)
    {
        $this->di = $di;
        Di::setDefault($di);
    }

    public function __get($property)
    {
        if ($this->di && $this->di->has($property)) {
            return $this->di->getShared($property);
        }
    }
    
    public function setUp()
    {
        self::$DI[] = $default = Di::getDefault();
        $defaultDi = get_class($default);
        $di = new $defaultDi;
        foreach ($default->getServices() as $name => $service) {
            $di->setRaw($name, $service);
        }
        $this->setDi($di);
    }

    public function tearDown()
    {
        Di::setDefault(array_pop(self::$DI));
    }
}
