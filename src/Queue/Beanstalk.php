<?php
namespace PhalconX\Queue;

use Phalcon\Di;
use PhalconX\Util;

class Beanstalk extends \Phalcon\Queue\Beanstalk
{
    private $cache;
    private $logger;

    public function __construct($options = null)
    {
        $this->cache = Util::service('cache', $options);
        $this->logger = Util::service('logger', $options, false);
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
    public function addJob(JobInterface $job, $delay = null)
    {
        $arguments = get_object_vars($job);
        $arguments['_handler'] = get_class($job);
        $jobId = $job->getId();
        if (isset($jobId)) {
            $arguments['_id'] = self::uuid();
            $this->cache->save($this->buildJobKey($jobId), $arguments['_id']);
        }
        if ($this->logger) {
            $this->logger->info('Add job ' . json_encode($arguments));
        }
        return $this->put($arguments, array(
            'delay' => isset($delay) ? $delay : $job->getDelay(),
            'ttr' => $job->getTtr(),
            'priority' => $job->getPriority()
        ));
    }
    
    public function processJobs($timeout = null)
    {
        $start = time();
        do {
            $beanstalkJob = $this->jobQueue->reserve($timeout);
            if ($beanstalkJob) {
                $this->handleJob($beanstalkJob);
            }
        } while (isset($timeout) && time() - $start > $timeout);
    }

    private static function uuid()
    {
        return uniqid('', true);
    }

    private function buildJobKey($jobId)
    {
        return 'job:' . $jobId;
    }

    private function createJob($handlerClass)
    {
        return Di::getDefault()->get($handlerClass);
    }
    
    private function handleJob($beanstalkJob)
    {
        $arguments = $beanstalkJob->getBody();
        if ($this->logger) {
            $this->logger->info("process job " . json_encode($arguments));
        }
        if (isset($arguments['_handler'])) {
            $job = $this->createJob($arguments['_handler']);
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
                    $beanstalkJob->delete();
                    return;
                }
            }
            foreach ($arguments as $key => $val) {
                if ($key && $key[0] != '_') {
                    $job->$key = $val;
                }
            }
            $job->process();
        }
        $beanstalkJob->delete();
    }
}
