<?php
namespace PhalconX\Cli;

use Phalcon\Cli\Router as BaseRouter;
use PhalconX\Di\Injectable;

class Router extends BaseRouter
{
    use Injectable;
    
    public function scan($dir, $module = null)
    {
    }

    public function handle($arguments = null)
    {
        $this->logger->info(implode(" ", $arguments));
    }
}
