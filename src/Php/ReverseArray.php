<?php
namespace PhalconX\Php;

/**
 * a one-to-many map.
 * fast to search key by value
 */
class ReverseArray implements \ArrayAccess, \Serializable
{
    private $container = [];

    private $reverse = [];

    public function serialize()
    {
        return serialize($this->container);
    }

    public function unserialize($data)
    {
        $this->container = unserialize($data);
        $this->reverse = [];
        foreach ($this->container as $key => $value) {
            foreach ($value as $elem) {
                $this->reverse[$elem][$key] = true;
            }
        }
    }

    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            throw new \InvalidArgumentException("key is required");
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException("value must be an array");
        }
        $this->container[$key] = $value;
        foreach ($value as $elem) {
            $this->reverse[$elem][$key] = true;
        }
    }

    public function offsetExists($key)
    {
        return isset($this->container[$key]);
    }

    public function offsetUnset($key)
    {
        if (isset($this->container[$key])) {
            foreach ($this->container[$key] as $elem) {
                unset($this->reverse[$elem][$key]);
            }
            unset($this->container[$key]);
        }
    }

    public function offsetGet($key)
    {
        return isset($this->container[$key]) ? $this->container[$key] : null;
    }

    public function getKeys($value)
    {
        return isset($this->reverse[$value]) ? array_keys($this->reverse[$value]) : null;
    }

    public function toArray()
    {
        return $this->container;
    }
}
