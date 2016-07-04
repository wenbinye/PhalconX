<?php
namespace PhalconX\Forms;

use Phalcon\Validation\Message\Group;

class Form extends \Phalcon\Forms\Form
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

    public function appendMessages($group)
    {
        foreach ($group as $message) {
            $this->appendMessage($message);
        }
    }
}
