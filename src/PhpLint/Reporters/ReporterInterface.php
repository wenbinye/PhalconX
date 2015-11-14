<?php
namespace PhalconX\PhpLint\Reporters;

use PhalconX\PhpLint\Errors\ErrorInterface;

interface ReporterInterface
{
    public function add(ErrorInterface $error);
}
