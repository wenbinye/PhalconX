<?php
namespace PhalconX\Cache;

use Psr\Cache\CacheItemInterface;
use DateTimeInterface;
use DateInterval;

class Item implements CacheItemInterface
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
    private $expiration;

    public function __construct($key, $value, $expiration, $isHit = true)
    {
        $this->key = $key;
        $this->value = $value;
        $this->expiration = $expiration;
        $this->isHit = $isHit;
    }

    public static function miss($key)
    {
        return new self($key, null, null, false);
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
        $this->isHit = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAt($expiration)
    {
        $this->expiration = $expiration instanceof DateTimeInterface
                         ? $expiration->getTimestamp()
                         : $expiration;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAfter($time)
    {
        $this->expiration = $time instanceof DateTimeInterface
                         ? (new DateTime())->add($time)->getTimestamp()
                         : time() + $time;
        return $this;
    }

    public function getExpiration()
    {
        return $this->expiration;
    }
}
