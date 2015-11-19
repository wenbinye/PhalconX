<?php
namespace PhalconX\Queue;

use Phalcon\Queue\Beanstalk\Job as BeanstalkJob;
use PhalconX\Mvc\SimpleModel;

/**
 * job 中任何公开成员变量都将作为任务参数。
 * 如果 job.id 非空，将检查是否有相同任务存在，如果有相同任务，将替换
 * 已经存在的任务。
 */
abstract class Job extends SimpleModel implements JobInterface
{
    const DEFAULT_DELAY = 0;       // no delay
    const DEFAULT_PRIORITY = 1024; // most urgent: 0, least urgent: 4294967295
    const DEFAULT_TTR = 60;        // 1 minute

    protected $delay = self::DEFAULT_DELAY;
    protected $priority = self::DEFAULT_PRIORITY;
    protected $ttr = self::DEFAULT_TTR;
    protected $runOnce;

    /**
     * @var Beanstalk
     */
    private $beanstalk;

    /**
     * @var BeanstalkJob
     */
    private $beanstalkJob;

    /**
     * @var array fields to serialize
     */
    private static $SERIALIZABLE_FIELDS;
    
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

    public function delete()
    {
        return $this->beanstalk->delete($this);
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

    public function getBeanstalk()
    {
        return $this->beanstalk;
    }

    public function setBeanstalk(Beanstalk $beanstalk)
    {
        $this->beanstalk = $beanstalk;
        return $this;
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

    private static function getSerialiableFields($class)
    {
        if (!self::$SERIALIZABLE_FIELDS[$class]) {
            $fields = [];
            $refl = new \ReflectionClass($class);
            foreach ($refl->getProperties() as $prop) {
                if ($prop->isStatic() || $prop->isPrivate()) {
                    continue;
                }
                $fields[] = $prop->getName();
            }
            self::$SERIALIZABLE_FIELDS[$class] = $fields;
        }
        return self::$SERIALIZABLE_FIELDS[$class];
    }

    public function __sleep()
    {
        return self::getSerialiableFields(get_class($this));
    }
}
