<?php
namespace PhalconX\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

class ArrayPool implements CacheItemPoolInterface
{
    private $values = [];
    
    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        if (array_key_exists($key, $this->values)) {
            list($value, $expiration) = $this->values[$key];
            if ($expiration === null || time() < $expiration) {
                return new Item($key, $value, $expiration);
            }
        }
        return Item::miss($key);
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = array())
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        return array_key_exists($key, $this->values);
    }
    
    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->values = [];
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        unset($this->values[$key]);
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
        $this->values[$item->getKey()] = [$item->get(), $item->getExpiration()];
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
