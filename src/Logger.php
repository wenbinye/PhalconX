<?php
namespace PhalconX;

use Phalcon\Logger as PhalconLogger;
use Psr\Log\LoggerInterface;
use Phalcon\Logger\Multiple;

class Logger extends Multiple implements LoggerInterface
{
    const EMERGENCY = PhalconLogger::EMERGENCY;
    const ALERT     = PhalconLogger::ALERT;
    const CRITICAL  = PhalconLogger::CRITICAL;
    const ERROR     = PhalconLogger::ERROR;
    const WARNING   = PhalconLogger::WARNING;
    const NOTICE    = PhalconLogger::NOTICE;
    const INFO      = PhalconLogger::INFO;
    const DEBUG     = PhalconLogger::DEBUG;
}
