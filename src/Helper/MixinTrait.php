<?php
namespace PhalconX\Helper;

trait MixinTrait
{
    private $origin;
    private $mixin;

    public function _setOrigin($origin)
    {
        $this->origin = $origin;
        return $this;
    }

    public function _setMixin($mixin)
    {
        $this->mixin = $mixin;
        return $this;
    }

    public function _callMixinOrOrigin($name, $args)
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
        return $this->_callMixinOrOrigin($name, $args);
    }
}
