<?php
namespace PhalconX\Queue;

abstract class Job implements JobInterface
{
    const DEFAULT_DELAY = 0; // no delay
    const DEFAULT_PRIORITY = 1024; // most urgent: 0, least urgent: 4294967295
    const DEFAULT_TTR = 60; // 1 minute

    public function getDelay()
    {
        return self::DEFAULT_DELAY;
    }

    public function getTtr()
    {
        return self::DEFAULT_TTR;
    }

    public function getPriority()
    {
        return self::DEFAULT_PRIORITY;
    }

    public function getId()
    {
        return null;
    }
}
