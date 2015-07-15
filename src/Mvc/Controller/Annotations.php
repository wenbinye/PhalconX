<?php
namespace PhalconX\Mvc\Controller;

use Phalcon\Di\Injectable;
use PhalconX\Util;
use PhalconX\Mvc\Controller\Filter\Json;
use PhalconX\Mvc\Controller\Filter\RequestMethod;
use PhalconX\Mvc\Controller\Filter\CsrfToken;
use PhalconX\Mvc\Controller\Filter\LoginOnly;
use PhalconX\Mvc\Controller\Filter\ACL;

class Annotations extends Injectable
{
    private $annotations;
    private $modelsMetadata;
    private $logger;

    private $filters = [
        'Json' => Json::CLASS,
        'PostOnly' => [RequestMethod::CLASS, ['POST']],
        'PutOnly' => [RequestMethod::CLASS, ['PUT']],
        'DeleteOnly' => [RequestMethod::CLASS, ['DELETE']],
        'RequestMethod' => RequestMethod::CLASS,
        'CsrfToken' => CsrfToken::CLASS,
        'LoginOnly' => LoginOnly::CLASS,
        'Acl' => Acl::CLASS
    ];
    
    public function __construct($options = null)
    {
        $this->annotations = Util::service('annotations', $options);
        $this->modelsMetadata = Util::service('modelsMetadata', $options, false);
        $this->logger = Util::service('logger', $options, false);
        if (isset($options['filters'])) {
            $this->filters = array_merge($this->filters, $options['filters']);
        }
    }
    
    public function beforeExecuteRoute($event, $dispatcher)
    {
        $controller = $dispatcher->getHandlerClass();
        if ($this->modelsMetadata) {
            $filters = $this->modelsMetadata->read($controller.'.filters');
        }
        if (!isset($filters)) {
            $filters = ['class' => [], 'methods' => []];
            $reflection = $this->annotations->get($controller);
            foreach ($reflection->getClassAnnotations() as $annotation) {
                if (isset($this->filters[$annotation->getName()])) {
                    $filters['class'][$annotation->getName()]
                        = [$annotation->getName(), $annotation->getArguments()];
                }
            }
            foreach ($reflection->getMethodsAnnotations() as $method => $annotations) {
                $method = strtolower($method);
                foreach ($annotations as $annotation) {
                    if (isset($this->filters[$annotation->getName()])) {
                        $filters['methods'][$method][$annotation->getName()]
                            = [$annotation->getName(), $annotation->getArguments()];
                    }
                }
            }
            if ($this->logger) {
                $this->logger->info("Parse filters from " . $controller);
            }
            if ($this->modelsMetadata) {
                $this->modelsMetadata->write($controller.'.filters', $filters);
            }
        }
        $method = strtolower($dispatcher->getActiveMethod());
        $methodFilters = [];
        if (!empty($filters['class'])) {
            $methodFilters = $filters['class'];
        }
        if (!empty($filters['methods'][$method])) {
            foreach ($filters['methods'][$method] as $name => $filter) {
                $methodFilters[$name] = $filter;
            }
        }
        foreach ($methodFilters as $filter) {
            $handler = $this->createFilter($filter);
            if ($handler->filter($dispatcher) === false) {
                return false;
            }
        }
    }
    
    private function createFilter($filter)
    {
        $config = $this->filters[$filter[0]];
        if (is_array($config)) {
            $filterClass = $config[0];
            $args = $config[1];
        } else {
            $filterClass = $config;
            $args = $filter[1];
        }
        if ($this->logger) {
            $this->logger->info("Apply filter " . $filter[0] . ' handler=' . $filterClass);
        }
        return $this->getDi()->get($filterClass, [$args]);
    }
}
