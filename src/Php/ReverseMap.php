<?php
namespace PhalconX\Php;

/**
 * a one-to-one map.
 * fast to search key by value
 */
class ReverseMap implements \ArrayAccess, \Serializable
{
    private $pos = 0;

    private $container = [];

    private $reverse = [];

    public function toArray()
    {
        return $this->container;
    }

    public function serialize()
    {
        return serialize([$this->container, $this->pos]);
    }

    public function unserialize($data)
    {
        list($this->container, $this->pos) = unserialize($data);
        $this->reverse = [];
        foreach ($this->container as $key => $value) {
            $this->reverse[$value] = $key;
        }
    }

    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $key = $this->pos;
            $this->pos++;
        }
        if (isset($this->container[$key])) {
            unset($this->reverse[$this->container[$key]]);
        }
        $this->container[$key] = $value;
        $this->reverse[$value] = $key;
    }

    public function offsetExists($key)
    {
        return isset($this->container[$key]);
    }

    public function offsetUnset($key)
    {
        if (isset($this->container[$key])) {
            unset($this->reverse[$this->container[$key]]);
            unset($this->container[$key]);
        }
    }

    public function offsetGet($key)
    {
        return isset($this->container[$key]) ? $this->container[$key] : null;
    }

    public function push($value)
    {
        if (isset($this->reverse[$value])) {
            return $this->reverse[$value];
        } else {
            $pos = $this->pos;
            $this->offsetSet(null, $value);
            return $pos;
        }
    }

    public function getKey($value)
    {
        return isset($this->reverse[$value]) ? $this->reverse[$value] : null;
    }
}
