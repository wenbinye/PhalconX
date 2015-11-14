<?php
namespace PhalconX\Queue;

use Phalcon\Di;
use Phalcon\Queue\Beanstalk as BaseQueue;
use Phalcon\Cache\BackendInterface as Cache;

/**
 * Beanstalk job queue
 */
class Beanstalk extends BaseQueue
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param Cache $cache
     * @param Logger $logger
     * @param array $options
     */
    public function __construct(Cache $cache, $logger = null, array $options = null)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        parent::__construct($options);
    }

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
     * 添加任务到队列
     *
     * job 中任何公开成员变量都将作为任务参数。
     * 如果 job.id 非空，将检查是否有相同任务存在，如果有相同任务，将替换
     * 已经存在的任务。
     *
     * @param JobInterface $job 任务对象
     */
    public function put($job, $options = null)
    {
        if ($job instanceof JobInterface) {
            $arguments = get_object_vars($job);
            $arguments['_handler'] = get_class($job);
            $jobId = $job->getId();
            if (isset($jobId)) {
                $arguments['_id'] = uniq();
                $this->cache->save($this->buildJobKey($jobId), $arguments['_id']);
            }
            if ($this->logger) {
                $this->logger->info('Add job ' . json_encode($arguments));
            }
            return parent::put($arguments, [
                'delay' => $job->getDelay(),
                'ttr' => $job->getTtr(),
                'priority' => $job->getPriority()
            ]);
        } else {
            return parent::put($job, $options);
        }
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
            if ($job) {
                $job->process();
                $job->delete();
            }
        } while (isset($timeout) && time() - $start > $timeout);
    }

    private function buildJobKey($jobId)
    {
        return 'job:' . $jobId;
    }

    private function convertJob($beanstalkJob)
    {
        if ($beanstalkJob) {
            $job = $this->createJob($beanstalkJob);
            if ($job) {
                return $job;
            } else {
                $beanstalkJob->delete();
            }
        }
    }

    private static function uuid()
    {
        return uniqid("_PHX.queue", true);
    }
    
    private function createJob($beanstalkJob)
    {
        $arguments = $beanstalkJob->getBody();
        if (isset($arguments['_handler']) && class_exists($arguments['_handler'])) {
            $job = Di::getDefault()->get($arguments['_handler']);
            if (isset($arguments['_id'])) {
                $jobId = $job->getId();
                $jobKey = $this->buildJobKey($jobId);
                $id = $this->cache->get($jobKey);
                if ($id === null) {
                    $this->cache->save($jobKey, self::uuid());
                } elseif ($id != $arguments['_id']) {
                    if ($this->logger) {
                        $this->logger->error("Duplicate job " . json_encode($arguments));
                    }
                    return;
                }
            }
            foreach ($arguments as $key => $val) {
                if ($key && $key[0] != '_') {
                    $job->$key = $val;
                }
            }
            $job->setBeanstalkJob($beanstalkJob);
            return $job;
        } else {
            if ($this->logger) {
                $this->logger->error("Job was not created properly: " . json_encode($arguments));
            }
        }
    }
}
