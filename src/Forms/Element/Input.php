<?php
namespace PhalconX\Forms\Element;

use Phalcon\Text;
use Phalcon\Tag;
use Phalcon\Forms\Element;
use Phalcon\Forms\ElementInterface;
use PhalconX\Util;
use PhalconX\Exception;

class Text extends Element implements ElementInterface
{
    public function render($attributes = null)
    {
        $attributes = $this->prepareAttributes($attributes);
        $type = Util::fetch($attributes, 'type', 'text');
        $method = Text::camelize($type) . 'Field';
        if (method_exists(Tag::CLASS, $method)) {
            return Tag::$method($attributes);
        } else {
            throw new Exception("Unknown input type $type");
        }
    }
}
