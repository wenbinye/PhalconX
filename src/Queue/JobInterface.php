<?php
namespace PhalconX\Queue;

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

    /**
     * @return string 任务唯一ID。返回 null 表示任务可在队列中运行任意次数
     */
    public function getId();
}
