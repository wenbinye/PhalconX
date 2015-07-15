<?php
namespace PhalconX\Exception;

class ValidationException extends \PhalconX\Exception
{
    private $errors;
    
    public function __construct($errors)
    {
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
