<?php
namespace PhalconX\Php\Lint\Errors;

class SyntaxError extends AbstractError
{
    public function getDescription()
    {
        return 'Syntax error';
    }
}
