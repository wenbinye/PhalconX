<?php
namespace PhalconX\Queue;

use Pheanstalk\Job as BeanstalkJob;
use Pheanstalk\PheanstalkInterface;
use PhalconX\Mvc\SimpleModel;

/**
 * job 中任何公开成员变量都将作为任务参数。
 * 如果 job.id 非空，将检查是否有相同任务存在，如果有相同任务，将替换
 * 已经存在的任务。
 */
abstract class Job extends SimpleModel implements JobInterface
{
    protected $delay = PheanstalkInterface::DEFAULT_DELAY;
    protected $priority = PheanstalkInterface::DEFAULT_PRIORITY;
    protected $ttr = PheanstalkInterface::DEFAULT_TTR;
    protected $runOnce;

    private $id;
    private $data;
    private $beanstalk;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->data;
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

    public function isRunOnce()
    {
        return $this->runOnce;
    }

    public function setRunOnce($runOnce)
    {
        $this->runOnce = $runOnce;
        return $this;
    }
    
    public function setBeanstalk(Beanstalk $beanstalk)
    {
        $this->beanstalk = $beanstalk;
        return $this;
    }

    public function delete()
    {
        return $this->beanstalk->delete($this);
    }
}
