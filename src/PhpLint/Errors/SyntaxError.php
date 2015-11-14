<?php
namespace PhalconX\PhpLint\Errors;

class SyntaxError extends AbstractError
{
    public function getDescription()
    {
        return 'Syntax error';
    }
}
