<?php
namespace PhalconX\Test;

use Phalcon\Di;
use PhalconX\Di\Injectable;

trait DiService
{
    use Injectable;

    private static $di;

    /**
     * @before
     */
    public function resetDi()
    {
        self::$di = Di::getDefault();
        $di = new Di;
        foreach (self::$di->getServices() as $name => $service) {
            $di->setRaw($name, $service);
        }
        Di::setDefault($di);
    }

    /**
     * @after
     */
    public function restoreDi()
    {
        Di::setDefault(self::$di);
    }
}
