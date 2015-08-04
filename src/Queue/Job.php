<?php
namespace PhalconX\Queue;

use PhalconX\Di\Injectable;
use Phalcon\Queue\Beanstalk\Job as BeanstalkJob;
use PhalconX\Util;
use PhalconX\Mvc\SimpleModel;

abstract class Job extends SimpleModel implements JobInterface
{
    use Injectable;
    
    const DEFAULT_DELAY = 0;       // no delay
    const DEFAULT_PRIORITY = 1024; // most urgent: 0, least urgent: 4294967295
    const DEFAULT_TTR = 60;        // 1 minute

    private $beanstalkJob;

    protected $logger;
    protected $id;
    protected $delay = self::DEFAULT_DELAY;
    protected $priority = self::DEFAULT_PRIORITY;
    protected $ttr = self::DEFAULT_TTR;

    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->logger = Util::service('logger', $options, false);
    }
    
    public function getDelay()
    {
        return $this->delay;
    }

    public function getTtr()
    {
        return $this->ttr;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function getId()
    {
        return $this->id;
    }

    public function delete()
    {
        return $this->beanstalkJob->delete();
    }

    public function release()
    {
        return $this->beanstalkJob->release();
    }

    public function bury()
    {
        return $this->beanstalkJob->bury();
    }

    public function touch()
    {
        return $this->beanstalkJob->touch();
    }

    public function kick()
    {
        return $this->beanstalkJob->kick();
    }

    public function stats()
    {
        return $this->beanstalkJob->stats();
    }
    
    public function getBeanstalkJob()
    {
        return $this->beanstalkJob;
    }

    public function setBeanstalkJob(BeanstalkJob $beanstalkJob)
    {
        $this->beanstalkJob = $beanstalkJob;
        return $this;
    }
}
