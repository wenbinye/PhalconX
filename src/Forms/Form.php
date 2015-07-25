<?php
namespace PhalconX\Forms;

use Phalcon\Validation\Message\Group;
use Phalcon\Forms\Form as BaseForm;

class Form extends BaseForm
{
    public function hasErrors()
    {
        return !empty($this->_messages);
    }

    public function appendMessage($message)
    {
        if (!isset($this->_messages[$message->getField()])) {
            $this->_messages[$message->getField()] = new Group;
        }
        $this->_messages[$message->getField()]->appendMessage($message);
    }
}
