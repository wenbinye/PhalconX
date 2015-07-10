<?php
namespace PhalconX;

class ValidationException extends \InvalidArgumentException
{
    private $errors;
    
    public function __construct($errors)
    {
        $this->errors = $errors;
        parent::__construct($this->getErrorMessages());
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getErrorMessages()
    {
        $messages = [];
        foreach ($this->errors as $error) {
            $messages[] = $error->getMessage();
        }
        return join("\n", $messages);
    }
}
