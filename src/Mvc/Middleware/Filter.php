<?php
namespace PhalconX\Mvc\Middleware;

use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use PhalconX\Annotation\Annotations;
use PhalconX\Mvc\Annotations\Filter\FilterInterface;
use PhalconX\Exception\HttpException;

class Filter implements InjectionAwareInterface, EventsAwareInterface
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
    /**
     * @var DiInterface
     */
    private $di;
    
    public function beforeExecuteRoute($event, $dispatcher)
    {
        $controllerClass = $dispatcher->getHandlerClass();
        $filters = $this->getAnnotations()->iterate($controllerClass)
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
                $this->getLogger()->debug("Apply filter $type");
                if ($filter[1]->filter() === false) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            if ($this->getEventsManager()->fire('dispatch:beforeException', $dispatcher, $e) === false) {
                return false;
            }
            throw new HttpException($e->getStatusCode(), null, $e);
        }
    }

    public function getAnnotations()
    {
        if ($this->annotations === null) {
            $this->annotations = $this->getDi()->getAnnotations();
        }
        return $this->annotations;
    }

    public function setAnnotations($annotations)
    {
        $this->annotations = $annotations;
        return $this;
    }

    public function getEventsManager()
    {
        if ($this->eventsManager === null) {
            $this->eventsManager = $this->getDi()->getEventsManager();
        }
        return $this->eventsManager;
    }

    public function setEventsManager(ManagerInterface $eventsManager)
    {
        $this->eventsManager = $eventsManager;
        return $this;
    }
    
    /**
     * @return Logger\AdapterInterface
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = $this->getAnnotations()->getLogger();
        }
        return $this->logger;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function getDi()
    {
        if ($this->di === null) {
            $this->di = Di::getDefault();
        }
        return $this->di;
    }

    public function setDi(DiInterface $di)
    {
        $this->di = $di;
        return $this;
    }
}
