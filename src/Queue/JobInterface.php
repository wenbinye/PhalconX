<?php
namespace PhalconX\Queue;

use Phalcon\Queue\Beanstalk\Job as BeanstalkJob;

interface JobInterface
{
    /**
     * @return int 任务延时
     */
    public function getDelay();

    /**
     * @return int 任务执行时间
     */
    public function getTtr();

    /**
     * @return int 优先级
     */
    public function getPriority();

    public function isRunOnce();

    /**
     * @return self
     */
    public function setRunOnce($isRunOnce);

    public function setBeanstalk(Beanstalk $queue);

    /**
     * 任务处理函数
     */
    public function process();

    public function delete();

    public function release();

    public function bury();

    public function touch();

    public function kick();

    public function stats();
    
    public function setBeanstalkJob(BeanstalkJob $job);
    
    public function getBeanstalkJob();
}
