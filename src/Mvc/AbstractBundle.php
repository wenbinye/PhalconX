<?php
namespace PhalconX\Mvc;

use Phalcon\DiInterface;
use PhalconX\Util;
use Phalcon\Mvc\ModuleDefinitionInterface;
use Phalcon\Text;

class AbstractBundle implements ModuleDefinitionInterface
{
    const METHOD_PREFIX = 'provide';

    private $options;

    public function __construct($options = null)
    {
        $this->options = $options;
    }

    public function getOption($name)
    {
        return Util::fetch($this->options, $name);
    }
    
    public function registerAutoloaders(DiInterface $di = null)
    {
    }

    public function registerServices(DiInterface $di)
    {
        $self = $this;
        $len = strlen(self::METHOD_PREFIX);
        $prefix = $this->getOption('prefix');
        foreach (get_class_methods($this) as $method) {
            if (Text::startsWith($method, self::METHOD_PREFIX)) {
                $service = substr($method, $len);
                if ($prefix) {
                    $service = $prefix . $service;
                } else {
                    $service = lcfirst($service);
                }
                $di[$service] = function () use ($di, $method, $self) {
                    return $self->$method($di);
                };
            }
        }
    }
}
