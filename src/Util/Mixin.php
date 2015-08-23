<?php
namespace PhalconX\Util;

trait Mixin
{
    private $origin;
    private $mixin;

    public function _setOriginObject($origin)
    {
        $this->origin = $origin;
        return $this;
    }

    public function _setMixin($mixin)
    {
        $this->mixin = $mixin;
        return $this;
    }

    public function _callMixin($name, $args)
    {
        if (method_exists($this->mixin, $name)) {
            array_unshift($args, $this->origin);
            return call_user_func_array([$this->mixin, $name], $args);
        } elseif (method_exists($this->origin, $name)) {
            return call_user_func_array([$this->origin, $name], $args);
        }
    }
    
    public function __call($name, $args)
    {
        return $this->_callMixin($name, $args);
    }
}
