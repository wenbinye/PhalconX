<?php
namespace PhalconX\Exception;

class AccessDeniedException extends FilterException
{
    public function __construct($message = null, $previous = null)
    {
        parent::__construct(403, $message, $previous);
    }
}
