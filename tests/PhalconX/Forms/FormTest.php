<?php
namespace PhalconX\Forms;

use PhalconX\Test\TestCase;
use Phalcon\Validation\Message;
use Phalcon\Forms\Element\Password;

/**
 * TestCase for Form
 */
class FormTest extends TestCase
{
    public function testAppendMessage()
    {
        $form = new Form;
        $form->add(new Password('password'));
        $form->appendMessage(new Message('password error', 'password'));
        $this->assertTrue($form->hasMessagesFor('password'));
    }
}
