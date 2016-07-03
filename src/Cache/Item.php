<?php
namespace PhalconX\Cache;

use Psr\Cache\CacheItemInterface;
use DateTimeInterface;
use DateInterval;

class Item extends CacheItemInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var bool
     */
    private $isHit;

    /**
     * @var int
     */
    private $expiresAt;

    public function __construct($key, $value, $isHit = true)
    {
        $this->key = $key;
        $this->value = $value;
        $this->isHit = $isHit;
    }
    
    /**
     * @inheritDoc
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * @inheritDoc
     */
    public function set($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAt($expiration)
    {
        $this->expiresAt = $expiration instanceof DateTimeInterface
                         ? $expiration->getTimestamp()
                         : $expiration;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAfter($time)
    {
        $this->expiresAt = $time instanceof DateTimeInterface
                         ? (new DateTime())->add($time)->getTimestamp()
                         : time() + $time;
        return $this;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }
}
