<?php
namespace PhalconX\Queue;

abstract class Job implements JobInterface
{
    const DEFAULT_DELAY = 0;       // no delay
    const DEFAULT_PRIORITY = 1024; // most urgent: 0, least urgent: 4294967295
    const DEFAULT_TTR = 60;        // 1 minute

    protected $id;
    protected $delay = self::DEFAULT_DELAY;
    protected $priority = self::DEFAULT_PRIORITY;
    protected $ttr = self::DEFAULT_TTR;

    public function __construct($data = null)
    {
        if ($data !== null) {
            foreach ($data as $key => $val) {
                if (property_exists($this, $key)) {
                    $this->$key = $val;
                }
            }
        }
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
}
