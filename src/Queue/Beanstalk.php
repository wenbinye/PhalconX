<?php
namespace PhalconX\Queue;

use PhalconX\DI\Injectable;

class Beanstalk extends \Phalcon\Queue\Beanstalk 
{
    use Injectable;
    
    public function addJob(JobInterface $job, $delay=null)
    {
        $arguments = get_object_vars($job);
        $arguments['_handler'] = get_class($job);
        $jobId = $job->getId();
        if (isset($jobId)) {
            $arguments['_id'] = self::uuid();
            $this->cache->save('job:'.$jobId, $arguments['_id']);
        }
        $this->logger->info('add job ' . json_encode($arguments));
        return $this->put($arguments, array(
            'delay' => isset($delay) ? $delay : $job->getDelay(),
            'ttr' => $job->getTtr(),
            'priority' => $job->getPriority()
        ));
    }
    
    public function processJobs($timeout=null)
    {
        $start_time = time();
        while (true) {
            $beanstalkJob = $this->jobQueue->reserve($timeout);
            if ($beanstalkJob) {
                $this->handleJob($beanstalkJob);
            }
            if (isset($timeout) && time() - $start_time > $timeout) {
                break;
            }
        }
    }

    private static function uuid()
    {
        return uniqid('', true);
    }
    
    private function handleJob($beanstalkJob)
    {
        $arguments = $beanstalkJob->getBody();
        $this->logger->info("process job " . json_encode($arguments));
        // 兼容处理
        if (isset($arguments['handler'])) {
            $name = implode('', array_map('ucfirst', explode('_', $arguments['handler'])));
            $arguments['_handler'] = 'Txf\Admin\Jobs\\'.$name;
        }
        if (isset($arguments['_handler'])) {
            $job = $this->getDI()->get($arguments['_handler']);
            if (isset($arguments['_id'])) {
                $jobId = $job->getId();
                $id = $this->cache->get('job:'.$jobId);
                // $this->logger->info("check id: $id <=> {$arguments['_id']}");
                if ($id == null) {
                    $this->cache->save('job:'.$jobId, self::uuid());
                } elseif ($id != $arguments['_id']) {
                    $this->logger->error("duplicate job " . json_encode($arguments));
                    $beanstalkJob->delete();
                    return;
                }
            }
            foreach ($arguments as $key => $val) {
                if ($key{0} != '_') {
                    $job->$key = $val;
                }
            }
            $job->process();
        }
        $beanstalkJob->delete();
    }
}
