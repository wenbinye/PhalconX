<?php
namespace PhalconX\Exception;

use Phalcon\Validation\Message;
use Phalcon\Validation\Message\Group;

class ValidationException extends \RuntimeException
{
    private $errors;
    
    public function __construct($errors)
    {
        if ($errors instanceof Message) {
            $group = new Group;
            $group->appendMessage($errors);
            $errors = $group;
        }
        $this->errors = $errors;
        $messages = [];
        foreach ($this->errors as $error) {
            $messages[] = $error->getMessage();
        }
        parent::__construct(join("; ", $messages));
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
