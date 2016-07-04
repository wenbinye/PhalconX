<?php
namespace PhalconX\Queue;

use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Cache;
use Phalcon\Logger;
use Pheanstalk\Connection;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;

/**
 * Beanstalk job queue
 */
class Beanstalk implements InjectionAwareInterface, PheanstalkInterface
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

    private $pheanstalk;

    public function __construct($parameters)
    {
        $host = isset($parameters['host']) ? $parameters['host'] : 'localhost';
        $port = isset($parameters['port']) ? $parameters['port'] : PheanstalkInterface::DEFAULT_PORT;
        $connectTimeout = isset($parameters['timeout']) ? $parameters['timeout'] : null;
        $connectPersistent = isset($parameters['persistent']) ? $parameters['persistent'] : false;
        $this->pheanstalk = new Pheanstalk($host, $port, $connectTimeout, $connectPersistent);
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->pheanstalk->setConnection($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->pheanstalk->getConnection();
    }

    // ----------------------------------------

    /**
     * {@inheritdoc}
     */
    public function bury($job, $priority = PheanstalkInterface::DEFAULT_PRIORITY)
    {
        return $this->pheanstalk->bury($job);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($job)
    {
        $this->pheanstalk->delete($job);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ignore($tube)
    {
        $this->pheanstalk->ignore($tube);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function kick($max)
    {
        return $this->pheanstalk->kick($max);
    }

    /**
     * {@inheritdoc}
     */
    public function kickJob($job)
    {
        $this->pheanstalk->kickJob($job);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function listTubes()
    {
        return $this->pheanstalk->listTubes();
    }

    /**
     * {@inheritdoc}
     */
    public function listTubesWatched($askServer = false)
    {
        return $this->pheanstalk->listTubesWatched($askServer);
    }

    /**
     * {@inheritdoc}
     */
    public function listTubeUsed($askServer = false)
    {
        return $this->pheanstalk->listTubeUsed($askServer);
    }

    /**
     * {@inheritdoc}
     */
    public function pauseTube($tube, $delay)
    {
        $this->pheanstalk->pauseTube($tube, $delay);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resumeTube($tube)
    {
        $this->pheanstalk->resumeTube($tube);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function peek($jobId)
    {
        return $this->convertJob($this->pheanstalk->peek($jobId));
    }

    /**
     * {@inheritdoc}
     */
    public function peekReady($tube = null)
    {
        return $this->convertJob($this->pheanstalk->peekReady($tube));
    }

    /**
     * {@inheritdoc}
     */
    public function peekDelayed($tube = null)
    {
        return $this->convertJob($this->pheanstalk->peekReady($tube));
    }

    /**
     * {@inheritdoc}
     */
    public function peekBuried($tube = null)
    {
        return $this->convertJob($this->pheanstalk->peekBuried($tube));
    }

    /**
     * {@inheritdoc}
     *
     * @param string|JobInterface $data
     */
    public function put(
        $data,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY,
        $ttr = PheanstalkInterface::DEFAULT_TTR
    ) {
        $saveJobId = null;
        if ($data instanceof JobInterface) {
            $job = $data;
            if ($job->isRunOnce()) {
                $cacheKey = self::CACHE_JOB . get_class($job);
                if (($jobId = $this->getCache()->get($cacheKey)) === null) {
                    $saveJobId = function ($jobId) use ($cacheKey, $job) {
                        $expire = $job->getDelay() + $job->getTtr() + 60;
                        $this->getCache()->save($cacheKey, 1, $expire);
                    };
                } else {
                    return $jobId;
                }
            }
            $priority = $job->getPriority();
            $delay = $job->getDelay();
            $ttr = $job->getTtr();
            $data = serialize($data);
        }

        $jobId = $this->pheanstalk->put($data, $priority, $delay, $ttr);
        if ($saveJobId) {
            call_user_func($saveJobId, $jobId);
        }
        return $jobId;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|JobInterface $data
     */
    public function putInTube(
        $tube,
        $data,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY,
        $ttr = PheanstalkInterface::DEFAULT_TTR
    ) {
        $this->useTube($tube);

        return $this->put($data, $priority, $delay, $ttr);
    }

    /**
     * {@inheritdoc}
     */
    public function release(
        $job,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY
    ) {
        return $this->pheanstalk->release($job, $priority, $delay);
    }

    /**
     * {@inheritdoc}
     */
    public function reserve($timeout = null)
    {
        return $this->convertJob($this->pheanstalk->reserve($timeout));
    }

    /**
     * {@inheritdoc}
     */
    public function reserveFromTube($tube, $timeout = null)
    {
        $this->watchOnly($tube);

        return $this->reserve($timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function statsJob($job)
    {
        return $this->pheanstalk->statsJob($job);
    }

    /**
     * {@inheritdoc}
     */
    public function statsTube($tube)
    {
        return $this->pheanstalk->statsTube($tube);
    }

    /**
     * {@inheritdoc}
     */
    public function stats()
    {
        return $this->pheanstalk->stats();
    }

    /**
     * {@inheritdoc}
     */
    public function touch($job)
    {
        $this->pheanstalk->touch($job);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function useTube($tube)
    {
        $this->pheanstalk->useTube($tube);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function watch($tube)
    {
        $this->pheanstalk->watch($tube);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function watchOnly($tube)
    {
        $this->pheanstalk->watchOnly($tube);
        return $this;
    }

    /**
     * Ignores the default tube
     */
    public function ignoreDefault()
    {
        return $this->ignore('default');
    }
    
    public function process($timeout = null)
    {
        $start = time();
        do {
            $job = $this->reserve($timeout);
            if ($job instanceof JobInterface) {
                $job->process();
            }
            $this->delete($job);
        } while (isset($timeout) && time() - $start > $timeout);
    }

    private function convertJob($pheanstalkJob)
    {
        if ($pheanstalkJob !== false) {
            $job = $this->createJob($pheanstalkJob);
            if ($job) {
                return $job;
            } else {
                return $pheanstalkJob;
            }
        } else {
            return false;
        }
    }
    
    private function createJob($pheanstalkJob)
    {
        $data = $pheanstalkJob->getData();
        $data = unserialize($data);
        if ($data === false) {
            return false;
        }

        if ($data instanceof JobInterface) {
            $job = $data;
            $job->setId($pheanstalkJob->getId());
            $job->setBeanstalk($this);
            return $job;
        } else {
            return false;
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
