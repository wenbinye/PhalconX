<?php
namespace PhalconX\Cli;

trait Console
{
    public function confirm($prompt)
    {
        $line = readline($prompt . ' [y/N] ');
        return in_array(strtolower(trim($line)), ['y', 'yes']);
    }
}
