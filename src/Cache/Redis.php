<?php
namespace PhalconX\Cache;

use Phalcon\Cache\FrontendInterface;
use Psr\Cache\CacheItemPoolInterface;
use Redis as RedisClient;
use RedisArray;

class Redis implements CacheItemPoolInterface
{
    private static $REDIS_ARRAY_OPTIONS = array(
        "previous",
        "function",
        "distributor",
        "index",
        "autorehash",
        "pconnect",
        "retry_interval",
        "lazy_connect",
        "connect_timeout",
    );

    private $frontend;
    private $options;
    private $connection;
    
    public function __construct(FrontendInterface $frontend, $options = [])
    {
        $this->frontend = $frontend;
        $this->options = array_merge(['prefix' => ''], $options);
    }

    private function buildKey($key)
    {
        return '_PHCR'. $this->options['prefix'] . $key;
    }

    public function getConnection()
    {
        if ($this->connection === null) {
            if (isset($this->options['servers'])) {
                $servers = [];
                foreach ($this->options['servers'] as $server) {
                    $port = 6379;
                    if (is_string($server)) {
                        $host = $server;
                    } elseif (isset($server['host'])) {
                        $host = $server['host'];
                    } elseif (isset($server['server'])) {
                        $host = $server['server'];
                    } elseif (isset($server[0])) {
                        $host = $server[0];
                        if (isset($server[1])) {
                            $port = $server[1];
                        }
                    }
                    $servers[] = [
                        'host' => $host,
                        'port' => isset($server['port']) ? (int) $server['port'] : $port,
                        'index' => isset($server['index']) ? $server['index'] : null
                    ];
                }
            } else {
                $servers = [['host' => '127.0.0.1', 'port' => 6379]];
            }
            if (count($servers) === 1) {
                $server = $servers[0];
                $redis = new RedisClient;
                $redis->connect($server['host'], $server['port']);
                if ($server['index']) {
                    $redis->select($server['index']);
                }
            } else {
                $serverArray = [];
                foreach ($servers as $server) {
                    $serverArray[] = $server['host'] .':' . $server['port']
                }
                $redis = new RedisArray($serverArray, ArrayHelper::select(
                    $this->options,
                    self::$REDIS_ARRAY_OPTIONS
                ));
            }
            $this->connection = $redis;
        }
        return $this->connection;
    }
    
    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        $value = $this->getConnection()->get($this->buildKey($key));
        if ($value === false) {
            return Item::miss($key);
        } else {
            return new Item($key, $value[0], $value[1]);
        }
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = array())
    {
        if (empty($keys)) {
            return [];
        }
        $items = [];
        $cacheKeys = [];
        foreach ($keys as $i => $key) {
            $cacheKeys[$key] = $this->buildKey($key);
        }
        $keys = array_keys($cacheKeys);
        $values = $this->getConnection()->mget(array_values($cacheKeys));
        foreach ($values as $i => $value) {
            $key = $keys[$i];
            if ($value === false) {
                $items[$key] = Item::miss($key);
            } else {
                $items[$key] = new Item($key, $value[0], $value[1]);
            }
        }
        return $items;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }
    
    /**
     * @inheritDoc
     */
    public function clear()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        $this->getConnection()->del($this->buildKey($key));
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys)
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item)
    {
        $expires = $item->getExpiration();
        if ($expires === null) {
            $expires = $this->frontend->getLifetime();
        } else {
            $expires = $expires - time();
            if ($expires < 1) {
                $expires = $this->frontend->getLifetime();
            }
        }
        $this->getConnection()->set(
            $key = $this->buildKey($item->getKey()),
            $expires,
            [$value = $item->get(), time() + $expires]
        );
        return true;
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->save($item);
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        return true;
    }
}
