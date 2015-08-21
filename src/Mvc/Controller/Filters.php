<?php
namespace PhalconX\Mvc\Controller;

use Phalcon\Di\Injectable;
use PhalconX\Util;
use PhalconX\Annotations\Mvc\Filter\AbstractFilter as Filter;
use PhalconX\Annotations\ContextType;

class Filters extends Injectable
{
    public function beforeExecuteRoute($event, $dispatcher)
    {
        $controller = $dispatcher->getHandlerClass();
        $filters = $this->annotations->getAnnotations($controller, Filter::CLASS, [
            ContextType::T_CLASS, ContextType::T_METHOD
        ]);
        if (empty($filters)) {
            return;
        }

        $method = strtolower($dispatcher->getActiveMethod());
        $methodFilters = [];
        // every type of filter , only one can apply
        foreach ($filters as $filter) {
            $type = get_class($filter);
            if ($filter->isClass()) {
                if (!isset($methodFilters[$type])) {
                    $methodFilters[$type] = $filter;
                }
            } elseif (strtolower($filter->getMethod()) == $method) {
                $methodFilters[$type] = $filter;
            }
        }
        if (empty($methodFilters)) {
            return;
        }
        try {
            foreach ($methodFilters as $type => $filter) {
                $this->logger->info("Apply filter $type");
                if ($filter->filter() === false) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            if ($this->eventsManager) {
                if ($this->eventsManager->fire('dispatch:beforeException', $dispatcher, $e) === false) {
                    return false;
                }
            }
            throw $e;
        }
    }
}
