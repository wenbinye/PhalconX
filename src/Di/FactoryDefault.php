<?php
namespace PhalconX\Di;

use Phalcon\Di;
use Phalcon\Di\Service;

/**
 * Smarter Di
 */
class FactoryDefault extends Di
{
    private $autoloads;

    /**
     * Constructor.
     *
     * Providers default services depends on PHP_SAPI
     *
     * @param String $sapi sapi name. Default is PHP_SAPI
     */
    public function __construct($sapi = null)
    {
        parent::__construct();
        if ($sapi === null) {
            $sapi = PHP_SAPI;
        }
        if ($sapi === 'cli') {
            $this->_services = [
                "router" =>             new Service("router", "Phalcon\\CLI\\Router", true),
                "dispatcher" =>         new Service("dispatcher", "Phalcon\\CLI\\Dispatcher", true),
                "modelsManager" =>      new Service("modelsManager", "Phalcon\\Mvc\\Model\\Manager", true),
                "modelsMetadata" =>     new Service("modelsMetadata", "Phalcon\\Mvc\\Model\\MetaData\\Memory", true),
                "filter" =>             new Service("filter", "Phalcon\\Filter", true),
                "escaper" =>            new Service("escaper", "Phalcon\\Escaper", true),
                "annotations" =>        new Service("annotations", "Phalcon\\Annotations\\Adapter\\Memory", true),
                "security" =>           new Service("security", "Phalcon\\Security", true),
                "eventsManager" =>      new Service("eventsManager", "Phalcon\\Events\\Manager", true),
                "transactionManager" => new Service("transactionManager", "Phalcon\\Mvc\\Model\\Transaction\\Manager", true)
            ];
        } else {
            $this->_services = [
                "router" =>             new Service("router", "Phalcon\\Mvc\\Router", true),
                "dispatcher" =>         new Service("dispatcher", "Phalcon\\Mvc\\Dispatcher", true),
                "url" =>                new Service("url", "Phalcon\\Mvc\\Url", true),
                "modelsManager" =>      new Service("modelsManager", "Phalcon\\Mvc\\Model\\Manager", true),
                "modelsMetadata" =>     new Service("modelsMetadata", "Phalcon\\Mvc\\Model\\MetaData\\Memory", true),
                "response" =>           new Service("response", "Phalcon\\Http\\Response", true),
                "cookies" =>            new Service("cookies", "Phalcon\\Http\\Response\\Cookies", true),
                "request" =>            new Service("request", "Phalcon\\Http\\Request", true),
                "filter" =>             new Service("filter", "Phalcon\\Filter", true),
                "escaper" =>            new Service("escaper", "Phalcon\\Escaper", true),
                "security" =>           new Service("security", "Phalcon\\Security", true),
                "crypt" =>              new Service("crypt", "Phalcon\\Crypt", true),
                "annotations" =>        new Service("annotations", "Phalcon\\Annotations\\Adapter\\Memory", true),
                "flash" =>              new Service("flash", "Phalcon\\Flash\\Direct", true),
                "flashSession" =>       new Service("flashSession", "Phalcon\\Flash\\Session", true),
                "tag" =>                new Service("tag", "Phalcon\\Tag", true),
                "session" =>            new Service("session", "Phalcon\\Session\\Adapter\\Files", true),
                "sessionBag" =>         new Service("sessionBag", "Phalcon\\Session\\Bag"),
                "eventsManager" =>      new Service("eventsManager", "Phalcon\\Events\\Manager", true),
                "transactionManager" => new Service("transactionManager", "Phalcon\\Mvc\\Model\\Transaction\\Manager", true),
                "assets" =>             new Service("assets", "Phalcon\\Assets\\Manager", true)
            ];
        }
    }

    /**
     * mark autoload service definition from service provider
     *
     * @param array $autoloads name of service. service alias
     * @param string|ServiceProvider $provider service provider
     * @param array options: shared, instances, prefix
     */
    public function autoload(array $autoloads, $provider, $options = [])
    {
        foreach ($autoloads as $name => $alias) {
            if (is_int($name)) {
                $name = $alias;
            }
            if (empty($name)) {
                throw new \InvalidArgumentException("Cannot not autoload empty service");
            }
            if (!empty($options['prefix'])) {
                $alias = $options['prefix'] . ucfirst($alias);
            }
            $shared = true;
            if (isset($options['shared'])) {
                if (is_array($options['shared'])) {
                    $shared = in_array($name, $options['shared']);
                } else {
                    $shared = $options['shared'];
                }
            } elseif (isset($options['instances'])) {
                $shared = !in_array($name, $options['instances']);
            }
            $this->set($alias, function () use ($name, $provider) {
                if (!is_object($provider)) {
                    $provider = $this->getShared($provider);
                }
                return $provider->provide($name, func_get_args());
            }, $shared);
        }
        return $this;
    }

    /**
     * load all service definition from service provider
     *
     * @param string|ServiceProvider $provider service provider
     * @param array $options non-shared service names
     *   - aliases
     *   - shared
     *   - instances
     *   - prefix
     */
    public function load($provider, $options = null)
    {
        if (!is_object($provider)) {
            $provider = $this->getShared($provider);
        }
        $names = $provider->getNames();
        if (isset($options['aliases'])) {
            $services = $this->createServiceAliases($names, $options['aliases']);
            unset($options['aliases']);
        } else {
            $services = $names;
        }
        $this->autoload($services, $provider, $options);
    }

    private function createServiceAliases($names, $aliases)
    {
        $keys = array_keys($aliases);
        $common = array_intersect($names, $keys);
        $names = array_diff($names, $keys);
        return array_merge($names, array_intersect_key($aliases, array_flip($common)));
    }
    
    public function safeGet($name)
    {
        return $this->has($name) ? $this->get($name) : null;
    }
}
