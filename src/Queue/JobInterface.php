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
    
    /**
     * @return string 任务唯一ID。返回 null 表示任务可在队列中运行任意次数
     */
    public function getId();

    public function setBeanstalkJob(BeanstalkJob $job);
    
    public function getBeanstalkJob();
}
