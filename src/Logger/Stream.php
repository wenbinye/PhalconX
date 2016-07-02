<?php
namespace PhalconX\Logger;

use Phalcon\Logger\Adapter\Stream as BaseLogger;
use Psr\Log\LoggerInterface;

class Stream extends BaseLogger implements LoggerInterface
{
}
