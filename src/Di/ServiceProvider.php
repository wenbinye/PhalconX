<?php
namespace PhalconX\Di;

use Phalcon\Text;
use Phalcon\Di\Service;
use Phalcon\Di\Exception;

/**
 * Service provider for autoload services
 */
abstract class ServiceProvider extends \Phalcon\Di\Injectable
{
    const METHOD_PREFIX = 'provide';

    /**
     * @var array service definitions
     */
    protected $services = [];

    /**
     * Creates service instance
     *
     * @param string $name service name
     * @param array $args arguments to create instance
     */
    public function provide($name, $args)
    {
        $services = $this->getServices();
        if (isset($services[$name])) {
            return (new Service($name, $services[$name]))
                ->resolve($args, $this->getDi());
        } else {
            $method = self::METHOD_PREFIX . $name;
            if (method_exists($this, $method)) {
                if (empty($args)) {
                    return $this->$method();
                } else {
                    return call_user_func_array([$this, $method], $args);
                }
            } else {
                throw new Exception("Cannot load '{$name}' from " . get_class($this));
            }
        }
    }

    /**
     * Gets all service names
     */
    public function getNames()
    {
        $names = array_keys($this->getServices());
        $len = strlen(self::METHOD_PREFIX);
        foreach (get_class_methods($this) as $method) {
            if (Text::startsWith($method, self::METHOD_PREFIX)) {
                $name = lcfirst(substr($method, $len));
                if ($name) {
                    $names[] = $name;
                }
            }
        }
        return $names;
    }

    protected function getServices()
    {
        return $this->services;
    }
}
