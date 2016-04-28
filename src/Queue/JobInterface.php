<?php
namespace PhalconX\Queue;

interface JobInterface
{
    public function getId();

    public function getData();

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

    public function delete();

    public function process();

    public function setId($id);

    public function setBeanstalk(Beanstalk $beanstalk);
}
