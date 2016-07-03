<?php
namespace PhalconX\Cache;

use Psr\Cache\CacheItemPoolInterface;

class ArrayPool implements CacheItemPoolInterface
{
    private $values;
    
    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        if (array_key_exists($key, $this->values)) {
            return new Item($key, $this->values[$key]);
        } else {
            return new Item($key, null, false);
        }
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = array())
    {
        $items = [];
        foreach ($keys as $key) {
            $items[] = $this->getItem($key);
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
        $this->values[$item->getKey()] = $item->get();
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
