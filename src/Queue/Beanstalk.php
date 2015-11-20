<?php
namespace PhalconX\Queue;

use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Cache;
use Phalcon\Logger;
use Phalcon\Queue\Beanstalk as BaseQueue;
use Phalcon\Queue\Beanstalk\Job as BeanstalkJob;

/**
 * Beanstalk job queue
 */
class Beanstalk extends BaseQueue implements InjectionAwareInterface
{
    const CACHE_JOB = '_PHX.job.';
    /**
     * @var DiInterface
     */
    private $di;

    /**
     * @var Cache\BackendInterface
     */
    private $cache;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Gets current watching tubes
     *
     * @return array
     */
    public function watching()
    {
        $this->write('list-tubes-watched');
        $response = $this->readYaml();
        if ($response[0] != "OK") {
            return false;
        }
        return $response[2];
    }

    /**
     * Ignores the tube
     */
    public function ignore($tube)
    {
        $this->write('ignore ' . $tube);
        $response = $this->readStatus();
        if ($response[0] != "WATCHING") {
            return false;
        }
        return $response[1];
    }

    /**
     * Ignores the default tube
     */
    public function ignoreDefault()
    {
        return $this->ignore('default');
    }
    
    /**
     * Inserts jobs into the queue
     *
     * @param mixed $job JobInterface or any data
     * @param array options
     */
    public function put($job, $options = null)
    {
        if ($job instanceof JobInterface) {
            if ($job->isRunOnce()) {
                $cacheKey = self::CACHE_JOB . get_class($job);
                if ($this->getCache()->get($cacheKey) === null) {
                    $expire = $job->getDelay() + $job->getTtr() + 60;
                    $this->getCache()->save($cacheKey, 1, $expire);
                } else {
                    return true;
                }
            }
            $options = [
                'delay' => $job->getDelay(),
                'ttr' => $job->getTtr(),
                'priority' => $job->getPriority()
            ];
        }
        $this->getLogger()->info('Add job ' . json_encode($job));
        return parent::put($job, $options);
    }

    /**
     * deletes job from queue
     *
     * @var JobInterface $job
     */
    public function delete(JobInterface $job)
    {
        if ($job->isRunOnce()) {
            $this->getCache()->delete(self::CACHE_JOB.get_class($job));
        }
        return $job->getBeanstalkJob()->delete();
    }

    public function reserve($timeout = null)
    {
        return $this->convertJob(parent::reserve($timeout));
    }

    public function peekReady()
    {
        return $this->convertJob(parent::peekReady());
    }

    public function peekBuried()
    {
        return $this->convertJob(parent::peekBuried());
    }
    
    public function process($timeout = null)
    {
        $start = time();
        do {
            $job = $this->reserve($timeout);
            if ($job instanceof JobInterface) {
                $job->process();
            }
            $job->delete();
        } while (isset($timeout) && time() - $start > $timeout);
    }

    private function convertJob($beanstalkJob)
    {
        if ($beanstalkJob) {
            $job = $this->createJob($beanstalkJob);
            if ($job) {
                return $job;
            } else {
                return $beanstalkJob;
            }
        }
    }
    
    private function createJob(BeanstalkJob $beanstalkJob)
    {
        $arguments = $beanstalkJob->getBody();
        $job = null;
        if (is_array($arguments)) {
            $job = $this->createOldJob($arguments, $beanstalkJob);
        } elseif ($arguments instanceof JobInterface) {
            $job = $arguments;
        }
        if (isset($job)) {
            $job->setBeanstalk($this)
                ->setBeanstalkJob($beanstalkJob);
        } else {
            $this->getLogger()->error("Job was not created properly: " . json_encode($arguments));
        }
        return $job;
    }

    private function createOldJob($arguments)
    {
        if (isset($arguments['_handler']) && class_exists($arguments['_handler'])) {
            $job = $this->getDi()->get($arguments['_handler']);
            $job->assign($arguments);
            return $job;
        }
    }

    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = $this->getDi()->getCache();
        }
        return $this->cache;
    }

    public function setCache($cache)
    {
        $this->cache = $cache;
        return $this;
    }
    
    /**
     * @return Logger\AdapterInterface
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $di = $this->getDi();
            if ($di->has('logger')) {
                $this->logger = $di->getLogger();
            } else {
                $logger = new Logger\Adapter\Stream('php://stderr');
                $logger->setLogLevel(Logger::WARNING);
                $this->logger = $logger;
            }
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
