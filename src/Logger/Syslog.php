<?php
namespace PhalconX\Logger;

use Phalcon\Logger\Adapter\Syslog as BaseLogger;
use Psr\Log\LoggerInterface;

class Syslog extends BaseLogger implements LoggerInterface
{
}
