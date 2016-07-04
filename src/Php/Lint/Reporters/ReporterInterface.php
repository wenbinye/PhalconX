<?php
namespace PhalconX\Php\Lint\Reporters;

use PhalconX\Php\Lint\Errors\ErrorInterface;

interface ReporterInterface
{
    public function add(ErrorInterface $error);
}
