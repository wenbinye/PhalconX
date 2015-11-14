<?php
namespace PhalconX\Forms\Element;

use Phalcon\Text;
use Phalcon\Tag;
use Phalcon\Forms\Element;
use Phalcon\Forms\ElementInterface;
use PhalconX\Helper\ArrayHelper;
use PhalconX\Exception;

class Input extends Element implements ElementInterface
{
    public function render($attributes = null)
    {
        $attributes = $this->prepareAttributes($attributes);
        $type = ArrayHelper::fetch($attributes, 'type', 'text');
        $method = Text::camelize($type) . 'Field';
        if (method_exists(Tag::class, $method)) {
            return Tag::$method($attributes);
        } else {
            throw new \InvalidArgumentException("Unknown input type $type");
        }
    }
}
