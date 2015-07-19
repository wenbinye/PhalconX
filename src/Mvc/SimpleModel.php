<?php
namespace PhalconX\Mvc;

abstract class SimpleModel implements \ArrayAccess
{
    public function __construct(array $data = null)
    {
        if ($data === null) {
            return;
        }
        foreach ($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }
  
    public function offsetExists($offset)
    {
         return isset($this->$offset);
    }
  
    public function offsetGet($offset)
    {
         return $this->$offset;
    }
  
    public function offsetSet($offset, $value)
    {
         $this->$offset = $value;
    }
  
    public function offsetUnset($offset)
    {
         unset($this->$offset);
    }
}
