<?php
namespace PhalconX\Mvc\Middleware;

use Phalcon\Events\Manager as EventsManager;
use PhalconX\Annotation\Annotations;
use PhalconX\Mvc\Annotations\Filter\FilterInterface;
use PhalconX\Exception\FilterException;

class Filter
{
    /**
     * @var Annotations
     */
    private $annotations;

    /**
     * @var EventsManager
     */
    private $eventsManager;

    /**
     * @var Logger
     */
    private $logger;
    
    public function __construct(Annotations $annotations, EventsManager $eventsManager, $logger = null)
    {
        $this->annotations = $annotations;
        $this->eventsManager = $eventsManager;
        $this->logger = $logger;
    }
    
    public function beforeExecuteRoute($event, $dispatcher)
    {
        $controllerClass = $dispatcher->getHandlerClass();
        $filters = $this->annotations->iterate($controllerClass)
            ->is(FilterInterface::class)
            ->onClassOrMethods()
            ->toArray();
        if (empty($filters)) {
            return;
        }

        $method = strtolower($dispatcher->getActiveMethod());
        $methodFilters = [];
        // Groups by class name. if more than one filter is same class,
        // the later one wins
        foreach ($filters as $i => $filter) {
            $type = get_class($filter);
            if ($filter->isOnClass()) {
                if (!isset($methodFilters[$type])) {
                    $methodFilters[$type] = [$i, $filter];
                }
            } elseif (strtolower($filter->getMethodName()) == $method) {
                $methodFilters[$type] = [$i, $filter];
            }
        }
        if (empty($methodFilters)) {
            return;
        }
        usort($methodFilters, function ($a, $b) {
            $diff = $a[1]->priority - $b[1]->priority;
            if ($diff == 0) {
                return $a[0] - $b[0];
            } else {
                return $diff < 0 ? -1 : 1;
            }
        });
        try {
            foreach ($methodFilters as $type => $filter) {
                if ($this->logger) {
                    $this->logger->debug("Apply filter $type");
                }
                if ($filter[1]->filter() === false) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            if ($this->eventsManager->fire('dispatch:beforeException', $dispatcher, $e) === false) {
                return false;
            }
            throw new FilterException(400, '', $e);
        }
    }
}
